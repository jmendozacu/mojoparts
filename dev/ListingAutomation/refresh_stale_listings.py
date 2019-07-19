import database_config as config
import pymysql
import csv
import os
from datetime import datetime
from datetime import timedelta

today = datetime.date(datetime.today())
tomorrow = datetime.date(datetime.today() + timedelta(days=1))
connection = pymysql.connect(host=config.mysql["host"], user=config.mysql["user"], password=config.mysql["passwd"], db=config.mysql["db"])
product_query = "SELECT cpe.sku, \
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
    adn.value as 'additional notes' \
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
    WHERE css.value = %s \
    ORDER BY cpe.sku \
    ;"
    
with open("active-listings.csv", "r") as input_file, \
    open(f"{today}-listings-to-end.csv", "w+", newline="") as end_listings_file, \
    open(f"{today}-diy-pfg-eBay.csv", "w+", newline="") as bulk_listings_file:

    input_reader = csv.reader(input_file)
    end_writer = csv.writer(end_listings_file)
    bulk_writer = csv.writer(bulk_listings_file)
    cursor = connection.cursor()

    next(input_reader)
    end_writer.writerow(["itemID", "cs-sku", "sold_qty", "stock_qty", "start_date", "end_date"])
    bulk_writer.writerow(["DO NOT REMOVE THIS ROW","Required Fields in ALL CAPS","","","","","","","Choose ONE per listing"," ","","","","","","Chose ONE per listing","Chose ONE per listing","Copy/Paste","Copy/Paste","","Copy/Paste","","","","","Add Remove or Rename UP TO 20 Item Specifics fields as needed. (Item Specifics fields limited to 65 characters.)","","","","","","","","","","",""])
    bulk_writer.writerow(["CS-LINE CODE","PART NUMBER","STORE ID","TITLE","subtitle","epid","LISTING TYPE","POSTAL CODE","LISTING DURATION","START PRICE","QUANTITY","CATEGORY","category Name","images","item description","CONDITION","HANDLING TIME","COUNTRY","CURRENCY","upc","SITE","FULFILLMENTPOLICYID","PAYMENTPOLICYID","RETURNPOLICYID","item id","Brand","Warranty","Fitment Type","Certifications","Placement on Vehicle","Manufacturer Part Number","Interchange Part Number","Surface Finish","Superseded Part Number","Partslink","OEM Number","Color"])

    for row in input_reader:
        cs_sku = row[4]
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
# FIXME: This is an inefficient way to do this.  It would be better to execute
#        the query once to fetch all the records, then loop through them.
                cursor.execute(product_query, (cs_sku,))
                record = cursor.fetchone()
                if record != None:
                    ebay_title = record[2]
                    epid = cs_sku[3:].replace("-", "").strip() 
                    bulk_writer.writerow([cs_sku[:3], \
                                          cs_sku[3:], \
                                          "1", \
                                          ebay_title, \
                                          epid, \
                                          "StoresFixedPrice", \
                                          "46074", \
                                          "GTC", \
                                          ])
connection.close()
