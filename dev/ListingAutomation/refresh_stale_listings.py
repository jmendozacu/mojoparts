import database_config as config
#TODO: move queries to separate import file
import pymysql
import csv
import os
from datetime import datetime
from datetime import timedelta


def get_image_string(mage_sku_, cursor):
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


# initialize variables
today = datetime.date(datetime.today())
tomorrow = datetime.date(datetime.today() + timedelta(days=1))
db_connection = pymysql.connect(host=config.mysql["host"], user=config.mysql["user"], password=config.mysql["passwd"], db=config.mysql["db"])
product_query = " \
        SELECT cpe.sku, \
        css.VALUE AS 'cs_sku', \
        ttl.VALUE AS 'ebay_title', \
        cat.VALUE AS 'ebay_category', \
        bi.VALUE AS 'base_image', \
        mpn.value as 'Manufacturer Part Number', \
        hol.value as 'Interchange Part Number', \
        plc.value as 'Placement on Vehicle', \
        sfc.value as 'Surface Finish', \
        plk.value as 'Partslink', \
        oem.value as 'OEM Number', \
        clr.value as 'Color', \
        adn.value as 'additional notes', \
        ecn.category_name \
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
        left JOIN catalog_product_entity_varchar plk ON plk.entity_id=cpe.entity_id AND plk.attribute_id=142 \
        left JOIN catalog_product_entity_varchar oem ON oem.entity_id=cpe.entity_id AND oem.attribute_id=140 \
        left JOIN catalog_product_entity_int clr ON clr.entity_id=cpe.entity_id AND clr.attribute_id=92 \
        left JOIN catalog_product_entity_text adn ON adn.entity_id=cpe.entity_id AND adn.attribute_id=168 \
        left JOIN mojo_ebay_category_names ecn ON ecn.category_number=cat.VALUE \
        WHERE css.value = %s \
        ORDER BY cpe.sku \
        ;"


# mainline
with open("active-listings.csv", "r") as input_file, \
    open(f"{today}-listings-to-end.csv", "w+", newline="") as end_listings_file, \
    open(f"{today}-diy-pfg-eBay.csv", "w+", newline="") as bulk_listings_file:

    input_reader = csv.reader(input_file)
    end_writer = csv.writer(end_listings_file)
    bulk_writer = csv.writer(bulk_listings_file)
    cursor = db_connection.cursor()

    next(input_reader)
    end_writer.writerow(["itemID", "cs-sku", "sold_qty", "stock_qty", "start_date", "end_date"])
    bulk_writer.writerow(["DO NOT REMOVE THIS ROW","Required Fields in ALL CAPS","","","","","","","Choose ONE per listing"," ","","","","","","Chose ONE per listing","Chose ONE per listing","Copy/Paste","Copy/Paste","","Copy/Paste","","","","","Add Remove or Rename UP TO 20 Item Specifics fields as needed. (Item Specifics fields limited to 65 characters.)","","","","","","","","","","",""])
    bulk_writer.writerow(["CS-LINE CODE","PART NUMBER","STORE ID","TITLE","subtitle","epid","LISTING TYPE","POSTAL CODE","LISTING DURATION","START PRICE","QUANTITY","CATEGORY","category Name","images","item description","CONDITION","HANDLING TIME","COUNTRY","CURRENCY","upc","SITE","FULFILLMENTPOLICYID","PAYMENTPOLICYID","RETURNPOLICYID","item id","Brand","Warranty","Fitment Type","Certifications","Placement on Vehicle","Manufacturer Part Number","Interchange Part Number","Surface Finish","Superseded Part Number","Partslink","OEM Number","Color"])

    for row in input_reader:
        # TODO: use a nametuple as the data structure for csv and row data
        cs_sku = row[4]
        cs_partno = cs_sku[4:]
        listing_sku = row[5]
        sold_qty = int(row[9])
        stock_qty = int(row[16])
        start_date = datetime.date(datetime.strptime(row[14], "%Y-%m-%d"))
        end_date = start_date + timedelta(days=30)

        # calculate the next end date
        while (end_date + timedelta(days=30)) < tomorrow:
           end_date = end_date + timedelta(days=30)

        if sold_qty == 0 and stock_qty > 0 and end_date == tomorrow:
            ebay_item = row[10]
            end_writer.writerow([ebay_item, cs_sku, sold_qty, stock_qty, start_date, end_date])

            # for non-catalog listings, build a bulk upload to recreate listings
            if cs_sku[:3] == "366":
                cursor.execute(product_query, (listing_sku))
                record = cursor.fetchone()
                if record != None:
                    mage_sku = record[0]
                    ebay_title = record[2]
                    epid = cs_partno.replace("-", "").strip() 
                    img_string = get_image_string(mage_sku, cursor)
                    item_description = record[12]
                    if item_description == "":
                        item_description = "none"
                    bulk_writer.writerow(
                            [cs_sku[:3], \
                            cs_partno, \
                            "1", \
                            ebay_title, \
                            epid, \
                            "StoresFixedPrice", \
                            "46074", \
                            "GTC", \
                            row[15], \
                            row[16], \
                            record[4], \
                            record[13], \
                            img_string, \
                            item_description, \
                            "1000||New", \
                            "2", \
                            "US", \
                            "", \
                            "US", \
                            "TODO: fulfillment policy id", \
                            "TODO: payment policy id", \
                            "TODO: return policy id", \
                            "Aftermarket Replacement", \
                            "1 Year", \
                            "Direct Replacement", \
                            "DOT/SAE", \
                            "TODO: placement on vehicle", \
                            "TODO: mpn", \
                            "TODO: interchange", \
                            "TODO: surface finish", \
                            "TODO: superseded part #", \
                            "TODO: partslink", \
                            "TODO: oem", \
                            "TODO: color", \
                            ])
db_connection.close()
