import csv
import os
from datetime import datetime
from datetime import timedelta

tomorrow = datetime.date(datetime.today() + timedelta(days=1))

with open("active-listings.csv", "r") as input_file,\
    open("listings-to-end.csv", "w", newline="") as end_listings_file:
    input_reader = csv.reader(input_file)
    end_writer = csv.writer(end_listings_file)

    next(input_reader)
    end_writer.writerow(["itemID", "cs-sku", "sold_qty", "stock_qty", "start_date", "end_date"])

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

# if it is a non-catalog listing (PFG), then add it to the bulk upload file to create new listings
