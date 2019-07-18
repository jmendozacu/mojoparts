import database_config as config
import csv
import os
from datetime import datetime
from datetime import timedelta

today = datetime.date(datetime.today())
tomorrow = datetime.date(datetime.today() + timedelta(days=1))

with open("active-listings.csv", "r") as input_file,\
    open(f"{today}-listings-to-end.csv", "w+", newline="") as end_listings_file, \
    open(f"{today}-diy-pfg-eBay.csv", "w+", newline="") as bulk_listings_file:

    input_reader = csv.reader(input_file)
    end_writer = csv.writer(end_listings_file)
    bulk_writer = csv.writer(bulk_listings_file)

    next(input_reader)
    end_writer.writerow(["itemID", "cs-sku", "sold_qty", "stock_qty", "start_date", "end_date"])
    bulk_writer.writerow(["DO NOT REMOVE THIS ROW","Required Fields in ALL CAPS","","","","","","","Choose ONE per listing"," ","","","","","","Chose ONE per listing","Chose ONE per listing","Copy/Paste","Copy/Paste","","Copy/Paste","","","","","Add Remove or Rename UP TO 20 Item Specifics fields as needed. (Item Specifics fields limited to 65 characters.)","","","","","","","","","","",""])
    bulk_writer.writerow(["CS-LINE CODE","PART NUMBER","STORE ID","TITLE","subtitle","epid","LISTING TYPE","POSTAL CODE","LISTING DURATION","START PRICE","QUANTITY","CATEGORY","category Name","images","item description","CONDITION","HANDLING TIME","COUNTRY","CURRENCY","upc","SITE","FULFILLMENTPOLICYID","PAYMENTPOLICYID","RETURNPOLICYID","item id","Brand","Warranty","Fitment Type","Certifications","Placement on Vehicle","Manufacturer Part Number","Interchange Part Number","Surface Finish","Superseded Part Number","Partslink","OEM Number","Color"])

    for row in input_reader:
        sold_qty = int(row[9])
        stock_qty = int(row[16])
        start_date = datetime.date(datetime.strptime(row[14], "%Y-%m-%d"))
        end_date = start_date + timedelta(days=30)

        # calculate the next end date
        while (end_date + timedelta(days=30)) < tomorrow:
           end_date = end_date + timedelta(days=30)

        if sold_qty == 0 and stock_qty > 0 and end_date == tomorrow:
            ebay_item = row[10]
            end_writer.writerow([ebay_item, row[4], sold_qty, stock_qty, start_date, end_date])

        # for non-catalog listings, build a bulk upload to recreate listings
