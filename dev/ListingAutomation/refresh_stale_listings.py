# Process a list of active listings to determine which ones need to be ended
# so that the listing can be refreshed.  Generate a csv containing the
# listings to be ended.  WHI will automatically regenerate any ended catalog
# listings, but this script will generate a separate csv to recreate any non-
# catalog listings.
#
# A listing is considered stale if it hasn't sold within the current listing
# period (30 days), so if a listing is ending tomorrow and is stale, then it
# should be refreshed.  But this script will also try to more evenly spread
# the listings out over the entire 30 days so that the # of listings ending
# on a given day is less "lumpy".  

import configparser
import csv
from datetime import datetime
from collections import namedtuple
import os
import pymysql
import sys
from datetime import timedelta

TODAY = datetime.date(datetime.today())
TOMORROW = datetime.date(datetime.today() + timedelta(days=1))
PFG = "366"
product_query = "\
    SELECT cpe.sku as 'sku', \
    css.VALUE AS 'cs_sku', \
    ttl.VALUE AS 'ebay_title', \
    cat.VALUE AS 'ebay_category', \
    bi.VALUE AS 'base_image', \
    mpn.value as 'Manufacturer Part Number', \
    hol.value as 'Interchange Part Number', \
    plc.value as 'Placement on Vehicle', \
    sfcv.value as 'Surface Finish', \
    plk.value as 'Partslink', \
    oem.value as 'OEM Number', \
    clrv.value as 'Color', \
    adn.value as 'additional notes', \
    ecn.category_name, \
    lp.listing_id \
    FROM catalog_product_entity cpe inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and \
        sts.attribute_id=96 and sts.value=1\
    inner JOIN catalog_product_entity_int v \
        ON v.entity_id=cpe.entity_id AND v.attribute_id=163 AND v.VALUE=36  \
    LEFT JOIN catalog_product_entity_varchar css \
        ON css.entity_id=cpe.entity_id AND css.attribute_id=223 \
    left JOIN catalog_product_entity_varchar ttl \
        ON ttl.entity_id=cpe.entity_id AND ttl.attribute_id=153 \
    left JOIN catalog_product_entity_varchar cat \
        ON cat.entity_id=cpe.entity_id AND cat.attribute_id=147 \
    left JOIN catalog_product_entity_varchar bi \
        ON bi.entity_id=cpe.entity_id AND bi.attribute_id=85 \
    left JOIN catalog_product_entity_varchar mpn \
        ON mpn.entity_id=cpe.entity_id AND mpn.attribute_id=177 \
    left JOIN catalog_product_entity_varchar hol \
        ON hol.entity_id=cpe.entity_id AND hol.attribute_id=138 \
    left JOIN catalog_product_entity_varchar plc \
        ON plc.entity_id=cpe.entity_id AND plc.attribute_id=143 \
    left JOIN catalog_product_entity_int sfc \
        ON sfc.entity_id=cpe.entity_id AND sfc.attribute_id=155 \
    left JOIN eav_attribute_option_value sfcv \
        ON sfcv.option_id=sfc.value \
    left JOIN catalog_product_entity_varchar plk \
        ON plk.entity_id=cpe.entity_id AND plk.attribute_id=142 \
    left JOIN catalog_product_entity_varchar oem \
        ON oem.entity_id=cpe.entity_id AND oem.attribute_id=140 \
    left JOIN catalog_product_entity_int clr \
        ON clr.entity_id=cpe.entity_id AND clr.attribute_id=92 \
    left JOIN eav_attribute_option_value clrv \
        ON clrv.option_id=clr.value \
    left JOIN catalog_product_entity_text adn \
        ON adn.entity_id=cpe.entity_id AND adn.attribute_id=168 \
    left JOIN mojo_ebay_category_names ecn \
        ON ecn.category_number=cat.VALUE \
    LEFT JOIN m2epro_listing_product lp \
        ON lp.product_id=cpe.entity_id \
    WHERE css.value = %s \
    ORDER BY cpe.sku \
    ;"
base_image_query = "\
    SELECT bi.value \
    FROM catalog_product_entity cpe \
    LEFT JOIN catalog_product_entity_varchar bi \
        ON bi.entity_id=cpe.entity_id AND bi.attribute_id=85 \
    WHERE cpe.sku= %s \
;"

gallery_query = "\
    SELECT mg.value \
    FROM catalog_product_entity cpe \
    left join catalog_product_entity_media_gallery mg \
        ON mg.entity_id = cpe.entity_id AND mg.attribute_id = 88 \
    left join catalog_product_entity_media_gallery_value mgv \
        ON mgv.value_id = mg.value_id \
    WHERE cpe.sku = %s \
    AND mgv.disabled = 0 \
    ORDER BY mgv.position \
    ;"
policy_dict = {59: 84611513020,
               60: 142133024020,
               61: 142132942020,
               62: 142133024020}
placement_dict = {"10": "Rear",
                  "11": "Front",
                  "12": "Right",
                  "13": "Left"}


def is_recently_sold(row_last_sale, row_sold_qty):
    sold_recently = True
    if row_last_sale == "" or row_sold_qty == "0":
        sold_recently = False
    else:
        last_sale = datetime.date(datetime.strptime(row_last_sale, "%m-%d-%Y"))
        if last_sale + timedelta(days=30) < TOMORROW:  # w/i 30 days?
            sold_recently = False
    return sold_recently


def get_image_string(mage_sku, cursor):
    img_list = []

    # The main image must be at the beginning of the image string
    cursor.execute(base_image_query, (mage_sku,))
    base_image_record = cursor.fetchone()
    if base_image_record is not None:
        img_list.append(base_image_record[0])

    # The remaining gallery images follow the main image
    cursor.execute(gallery_query, (mage_sku,))
    for gallery_record in cursor.fetchall():
        if gallery_record[0] not in img_list:
            img_list.append(gallery_record[0])

    # Build the properly formatted string of image URLs
    img_url_prefix = "http://mojoparts.com/media/catalog/product"
    img_string = ""
    i = 0
    for img in img_list:
        if img_string != "":
            img_string = img_string + "||"
        img_string = img_string + img_url_prefix + img
        i = i + 1

        # The image string cannot contain more than 5 images
        if i == 4:
            break
    return img_string


def refresh_listing(row, cursor, r_end_writer, r_bulk_writer):
    # This function will add a listing to a csv containing all the listings to
    # be ended.  If the listing is for a non-catalog product (PFG), it will also
    # add a record to the csv used to bulk create new listings (since these
    # will not be automatically generated by WHI.

    linecode = row.cs_sku[:3]
    r_end_writer.writerow([row.ebay_item])

    # For non-catalog listings, add to the bulk upload to recreate listings
    if linecode == PFG:
        cursor.execute(product_query, row.listing_sku)
        record = cursor.fetchone()
        if record is not None:
            mage_sku = record[0]
            ebay_title = record[2]
            epid = "C" + mage_sku.replace("-", "").strip()
            img_string = get_image_string(mage_sku, cursor)
            item_description = record[12]
            if item_description is None or item_description == "":
                item_description = "No further information"
            placement = ""
            if record[7] is not None:
                for p in record[7].split(","):
                    if placement != "":
                        placement = placement + ","
                    placement = placement + placement_dict[p]
            r_bulk_writer.writerow(
                [linecode,
                 row.partno,
                 "1",
                 ebay_title,
                 "",
                 epid,
                 "StoresFixedPrice",
                 "46074",
                 "GTC",
                 row.list_price,
                 row.stock_qty,
                 record[3],
                 record[13],
                 img_string,
                 item_description.replace("\r\n", "<br>").replace("\n", "<br>"),
                 "1000||New",
                 "2",
                 "US",
                 "USD",
                 "",
                 "US",
                 policy_dict[record[14]],
                 "127723857020",
                 "127723856020",
                 "",
                 "Aftermarket Replacement",
                 "1 Year",
                 "Direct Replacement",
                 "DOT/SAE",
                 placement,
                 record[5],
                 record[6],
                 record[8],
                 record[10],
                 record[9],
                 record[10],
                 record[11]
                 ])
            print("+", end="", flush=True)


def main():
    # The first argument is the config file name
    config = configparser.ConfigParser()
    config.sections()
    config.read(sys.argv[1])

    db_connection = pymysql.connect(host=config['DATABASE']['Host'],
                                    user=config['DATABASE']['User'],
                                    password=config['DATABASE']['Passwd'],
                                    db=config['DATABASE']['Db'])
    Listing = namedtuple("Listing",
                         "rownum, store, ebay_status, pf_status, cs_sku, "
                         "listing_sku, brand_name, partno, last_sale,"
                         "sold_qty, ebay_item, item_title, item_location, gsp, "
                         "start_date, list_price, stock_qty, hit_count")

    # Based on the total number of active listings, determine how many should be
    # analyzed to be refreshed.  The goal is to eventually distribute the
    # listings evenly across 30 days without unnecessarily ending listings
    # prematurely.  So we only analyze 1/30th of the listings ending the
    # soonest, and if they haven't performed well then we'll refresh them.
    pfg_goal = 0
    other_goal = 0
    with open(f"{config['FILES']['ActiveListings']}.csv", "r") as input_file:
        reader = csv.reader(input_file)
        next(reader)
        pfg_total = 0
        other_total = 0
        for csv_row in map(Listing._make, reader):
            if csv_row.cs_sku[:3] == PFG:
                pfg_total = pfg_total + 1
            else:
                other_total = other_total + 1
        pfg_goal = int(round(pfg_total / 30, 0))
        other_goal = int(round(other_total / 30, 0))

        # in case # listings < 30
        if 0 > pfg_total < 30:
            pfg_goal = 1
        if 0 > other_total < 30:
            other_goal = 1

        print(f"Total PFG listings = {pfg_total:,}, goal = {pfg_goal:,}")
        print(f"Total other listings = {other_total:,}, goal = {other_goal:,}", flush=True)

    pfg_count = 0
    other_count = 0
    with open(f"{config['FILES']['ListingsToEnd']}-{TODAY}.csv", "w+",
            newline="") as end_listings_file, \
        open(f"{config['FILES']['ListingsToRecreate']}-{TODAY}.csv", "w+",
                newline="") as bulk_listings_file:
        end_writer = csv.writer(end_listings_file)
        bulk_writer = csv.writer(bulk_listings_file)
        bulk_writer.writerow(["DO NOT REMOVE THIS ROW",
                              "Required Fields in ALL CAPS", "", "", "", "", "", "",
                              "Choose ONE per listing", " ", "", "", "", "", "",
                              "Chose ONE per listing", "Chose ONE per listing",
                              "Copy/Paste", "Copy/Paste", "", "Copy/Paste", "", "",
                              "", "", "Add Remove or Rename UP TO 20 Item "
                                      "Specifics fields as needed. (Item Specifics"
                                      "fields limited to 65 characters.)"
                                 , "", "", "", "", "", "", "", "", "", "", ""])
        bulk_writer.writerow(["CS-LINE CODE", "PART NUMBER", "STORE ID", "TITLE",
                              "subtitle", "epid", "LISTING TYPE", "POSTAL CODE",
                              "LISTING DURATION", "START PRICE", "QUANTITY",
                              "CATEGORY", "category Name", "images",
                              "item description", "CONDITION", "HANDLING TIME",
                              "COUNTRY", "CURRENCY", "upc", "SITE",
                              "FULFILLMENTPOLICYID", "PAYMENTPOLICYID",
                              "RETURNPOLICYID", "item id", "Brand", "Warranty",
                              "Fitment Type", "Certifications",
                              "Placement on Vehicle", "Manufacturer Part Number"
                                 , "Interchange Part Number", "Surface Finish",
                              "Superseded Part Number", "Partslink", "OEM Number",
                              "Color"])
        db_cursor = db_connection.cursor()
        target_end_date = TOMORROW
        while pfg_count < pfg_goal or other_count < other_goal:
            print(f"\nProcessing listings ending {target_end_date}...")
            print(f"\tPFG:{pfg_count:,}/{pfg_goal:,}")
            print(f"\tOther:{other_count:,}/{other_goal:,}")
            with open(f"{config['FILES']['ActiveListings']}.csv", "r") \
                     as input_file:
                csv_reader = csv.reader(input_file)
                next(csv_reader)  # ignore the csv header row
                i = 0
                for csv_row in map(Listing._make, csv_reader):
                    i = i + 1
                    if i % 10000 == 0:
                        print(".", end="", flush=True)

                    csv_linecode = csv_row.cs_sku[:3]
                    if (csv_linecode == PFG and pfg_count < pfg_goal) or \
                            (csv_linecode != PFG and other_count < other_goal):

                        # Calculate the next end date based on the calculated
                        # most recent start date.
                        start_date = datetime.date(
                            datetime.strptime(csv_row.start_date, "%Y-%m-%d"))
                        end_date = start_date + timedelta(days=30)
                        while end_date < target_end_date:
                            end_date = end_date + timedelta(days=30)

                        if end_date == target_end_date:
                            if not is_recently_sold(csv_row.last_sale, csv_row.sold_qty) \
                                    and int(csv_row.stock_qty) > 0:
                                refresh_listing(csv_row,
                                                db_cursor,
                                                end_writer,
                                                bulk_writer)

                            if csv_linecode == PFG:
                                pfg_count = pfg_count + 1
                            else:
                                other_count = other_count + 1
                            if pfg_count == pfg_goal and other_count == other_goal:
                                break
                target_end_date = target_end_date + timedelta(days=1)

    db_connection.close()
    print("\nScript complete!  Goal # of listings processed:")
    print(f"PFG:{pfg_count:,}/{pfg_goal:,}, Other:{other_count:,}/{other_goal:,}.")


if __name__ == '__main__':
    main()
