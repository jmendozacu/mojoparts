# TODO: comment this better so it makes sense in the future
# TODO: add unit testing
# TODO: add logging
# TODO: add the ability to pass config file options on command line

import csv
import os
import pymysql
import database_config as config
from datetime import datetime
from collections import namedtuple
from datetime import timedelta


TODAY = datetime.date(datetime.today())
TOMORROW = datetime.date(datetime.today() + timedelta(days=1))
PFG = "366"

def is_recently_sold(row_last_sale, row_sold_qty):
    sold_recently = True
    if (row_last_sale == "" or row_sold_qty == "0"):
        sold_recently = False
    else:
        last_sale = datetime.date(datetime.strptime(row_last_sale, "%m-%d-%Y"))
        if (last_sale+timedelta(days=30) < TOMORROW):  # w/i 30 days?
            sold_recently = False
    return sold_recently

def get_image_string(mage_sku, cursor):
    base_image_query = " \
            SELECT bi.value \
            FROM catalog_product_entity cpe \
            LEFT JOIN catalog_product_entity_varchar bi ON bi.entity_id=cpe.entity_id AND bi.attribute_id=85 \
            WHERE cpe.sku= %s \
    ;"
    gallery_query = " \
            SELECT mg.value \
            FROM catalog_product_entity cpe \
            left join catalog_product_entity_media_gallery mg ON mg.entity_id=cpe.entity_id AND mg.attribute_id=88 \
            left join catalog_product_entity_media_gallery_value mgv ON mgv.value_id=mg.value_id \
            WHERE cpe.sku= %s \
            AND mgv.disabled=0 \
            ORDER BY mgv.position \
    ;"
    img_list = []

    # add the base image to the beginning of the image string
    cursor.execute(base_image_query, (mage_sku,))
    base_image_record = cursor.fetchone()
    if base_image_record != None:
        img_list.append(base_image_record[0])

    # add the remaining gallery images to the end of the image string
    cursor.execute(gallery_query, (mage_sku,))
    for gallery_record in cursor.fetchall():
        if gallery_record[0] not in img_list:
            img_list.append(gallery_record[0])

    # build the formatted image string
    img_url_prefix = "http://mojoparts.com/media/catalog/product"
    img_string = ""
    i = 0
    for img in img_list:
        if img_string != "":
            img_string = img_string + "||"
        img_string = img_string + img_url_prefix + img
        if i == 4: # this list can't have more than 5 images
            break
    return img_string

def process_refresh(tgt_end_date, row, cursor):
    linecode = csv_row.cs_sku[:3]
    product_query = " \
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
            FROM catalog_product_entity cpe inner join catalog_product_entity_int sts on cpe.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1\
            inner JOIN catalog_product_entity_int v ON v.entity_id=cpe.entity_id AND v.attribute_id=163 AND v.VALUE=36  \
            LEFT JOIN catalog_product_entity_varchar css ON css.entity_id=cpe.entity_id AND css.attribute_id=223 \
            left JOIN catalog_product_entity_varchar ttl ON ttl.entity_id=cpe.entity_id AND ttl.attribute_id=153 \
            left JOIN catalog_product_entity_varchar cat ON cat.entity_id=cpe.entity_id AND cat.attribute_id=147 \
            left JOIN catalog_product_entity_varchar bi ON bi.entity_id=cpe.entity_id AND bi.attribute_id=85 \
            left JOIN catalog_product_entity_varchar mpn ON mpn.entity_id=cpe.entity_id AND mpn.attribute_id=177 \
            left JOIN catalog_product_entity_varchar hol ON hol.entity_id=cpe.entity_id AND hol.attribute_id=138 \
            left JOIN catalog_product_entity_varchar plc ON plc.entity_id=cpe.entity_id AND plc.attribute_id=143 \
            left JOIN catalog_product_entity_int sfc ON sfc.entity_id=cpe.entity_id AND sfc.attribute_id=155 \
            left JOIN eav_attribute_option_value sfcv ON sfcv.option_id=sfc.value \
            left JOIN catalog_product_entity_varchar plk ON plk.entity_id=cpe.entity_id AND plk.attribute_id=142 \
            left JOIN catalog_product_entity_varchar oem ON oem.entity_id=cpe.entity_id AND oem.attribute_id=140 \
            left JOIN catalog_product_entity_int clr ON clr.entity_id=cpe.entity_id AND clr.attribute_id=92 \
            left JOIN eav_attribute_option_value clrv ON clrv.option_id=clr.value \
            left JOIN catalog_product_entity_text adn ON adn.entity_id=cpe.entity_id AND adn.attribute_id=168 \
            left JOIN mojo_ebay_category_names ecn ON ecn.category_number=cat.VALUE \
            LEFT JOIN m2epro_listing_product lp ON lp.product_id=cpe.entity_id \
            WHERE css.value = %s \
            ORDER BY cpe.sku \
            ;"
    policy_dict = {59:84611513020,
                    60:142133024020,
                    61:142132942020,
                    62:142133024020}
    placement_dict = {"10":"Rear",
                      "11":"Front",
                      "12":"Right",
                      "13":"Left"}
    recently_sold = is_recently_sold(row.last_sale, row.sold_qty)

    # calculate the next end date based on the most recent start date
    start_date = datetime.date(datetime.strptime(row.start_date, "%Y-%m-%d"))
    end_date = start_date + timedelta(days=30)
    while end_date < tgt_end_date:
        end_date = end_date + timedelta(days=30)

    if not recently_sold\
            and int(row.stock_qty) > 0\
            and end_date == target_end_date:
        end_writer.writerow([row.ebay_item, end_date])

        # for non-catalog listings, build a bulk upload to recreate listings
        if linecode == PFG:
           cursor.execute(product_query, (row.listing_sku))
           record = cursor.fetchone()
           if record != None:
                mage_sku = record[0]
                ebay_title = record[2]
                epid = "C"+mage_sku.replace("-", "").strip() 
                img_string = get_image_string(mage_sku, cursor)
                item_description = record[12]
                if item_description == "":
                    item_description = "none"
                placement = ""
                if record[7] != None:
                    for p in record[7].split(","):
                        if placement != "":
                            placement = placement + ","
                        placement = placement + placement_dict[p]
                bulk_writer.writerow(
                        [linecode, \
                        row.partno, \
                        "1", \
                        ebay_title, \
                        "", \
                        epid, \
                        "StoresFixedPrice", \
                        "46074", \
                        "GTC", \
                        row.list_price, \
                        row.stock_qty, \
                        record[3], \
                        record[13], \
                        img_string, \
                        item_description.replace("\r\n","<br>").replace("\n","<br>"), \
                        "1000||New", \
                        "2", \
                        "US", \
                        "USD", \
                        "", \
                        "US", \
                        policy_dict[record[14]], \
                        "127723857020", \
                        "127723856020", \
                        "", \
                        "Aftermarket Replacement", \
                        "1 Year", \
                        "Direct Replacement", \
                        "DOT/SAE", \
                        placement, \
                        record[5], \
                        record[6], \
                        record[8], \
                        record[10], \
                        record[9], \
                        record[10], \
                        record[11] \
                        ])
        return True
    else:
        return False
    

# initialize variables
db_connection = pymysql.connect(host=config.mysql["host"], user=config.mysql["user"], password=config.mysql["passwd"], db=config.mysql["db"])

# build a data structure to hold the csv row data based on the column names
Listing = namedtuple("Listing", "rownum, store, ebay_status, pf_status, cs_sku, listing_sku, brand_name, partno, last_sale, sold_qty, ebay_item, item_title, item_location, gsp, start_date, list_price, stock_qty, hit_count")

# first, gather statistics about # of listings to refresh
pfg_goal = 0
other_goal = 0
with open("data/active-listings.csv", "r") as input_file:
    reader = csv.reader(input_file)
    next(reader)
    pfg_total = 0
    other_total = 0
    for row in map(Listing._make, reader):
        recently_sold = is_recently_sold(row.last_sale, row.sold_qty)
        if int(row.stock_qty) > 0 and not recently_sold:
            if row.cs_sku[:3] == PFG:
                pfg_total = pfg_total + 1
            else:
                other_total = other_total + 1
    pfg_goal = int(round(pfg_total/30, 0))
    other_goal = int(round(other_total/30, 0))
    print(f"Total PFG={pfg_total}, so we're ending {pfg_goal}")
    print(f"Total Other={other_total}, so we're ending {other_goal}")

# based on gathered statistics, refresh the correct # of listings
pfg_count = 0
other_count = 0
with open(f"data/{TODAY}-listings-to-end.csv", "w+", newline="") as end_listings_file, \
    open(f"data/{TODAY}-diy-pfg-eBay.csv", "w+", newline="") as bulk_listings_file:
    end_writer = csv.writer(end_listings_file)
    bulk_writer = csv.writer(bulk_listings_file)
    bulk_writer.writerow(["DO NOT REMOVE THIS ROW","Required Fields in ALL CAPS","","","","","","","Choose ONE per listing"," ","","","","","","Chose ONE per listing","Chose ONE per listing","Copy/Paste","Copy/Paste","","Copy/Paste","","","","","Add Remove or Rename UP TO 20 Item Specifics fields as needed. (Item Specifics fields limited to 65 characters.)","","","","","","","","","","",""])
    bulk_writer.writerow(["CS-LINE CODE","PART NUMBER","STORE ID","TITLE","subtitle","epid","LISTING TYPE","POSTAL CODE","LISTING DURATION","START PRICE","QUANTITY","CATEGORY","category Name","images","item description","CONDITION","HANDLING TIME","COUNTRY","CURRENCY","upc","SITE","FULFILLMENTPOLICYID","PAYMENTPOLICYID","RETURNPOLICYID","item id","Brand","Warranty","Fitment Type","Certifications","Placement on Vehicle","Manufacturer Part Number","Interchange Part Number","Surface Finish","Superseded Part Number","Partslink","OEM Number","Color"])
    db_cursor = db_connection.cursor()

    target_end_date = TOMORROW
    while pfg_count < pfg_goal or other_count < other_goal:
        with open("data/active-listings.csv", "r") as input_file:
            reader = csv.reader(input_file)
            next(reader)  # ignore header row

            for csv_row in map(Listing._make, reader):
                csv_linecode = csv_row.cs_sku[:3]
                if (csv_linecode == PFG and pfg_count < pfg_goal) or\
                        (csv_linecode != PFG and other_count < other_goal):

                    is_refreshed = process_refresh(target_end_date, csv_row, db_cursor)

                    if is_refreshed:
                        if csv_linecode == PFG:
                            pfg_count = pfg_count + 1
                        else:
                            other_count = other_count + 1

                if pfg_count == pfg_goal and other_count == other_goal:
                    break;
            target_end_date = target_end_date + timedelta(days=1)
db_connection.close()
