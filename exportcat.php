<?php

require("app/Mage.php");  // load the main Mage file
Mage::app();   // not run() because you just want to load Magento, not run it.

// load all of the active categories in the system and include all attributes
$categories = Mage::getModel('catalog/category')
              ->getCollection()
              ->addAttributeToSelect('*')
              ->addIsActiveFilter();

 $export_file = "var/export/categories.csv"; // assumes that you're running from the web root. var/ is typically writable
 $export = fopen($export_file, 'w') or die("Permissions error."); // open the file for writing.  if you see the error then check the folder permissions.

 $output = "";

 $output = "category_id,categories,description,url_key\r\n"; // column names. end with a newline.
 fwrite($export, $output); // write the file header with the column names




 foreach ($categories as $category) {
     $output = ""; // re-initialize $output on each iteration
     $output .= $category->getId().','; // no quote - integer
     $output .= '"'.$category->getName().'",'; // quotes - string
     $output .= '"'.$category->getDescription().'",'; // quotes - string
     $output .= '"'.$category->getUrl_key().'",'; // quotes - string
     // add any other fields you want here 
     $output .= "\r\n"; // add end of line
     fwrite($export, $output); // write to the file handle "$export" with the string "$output".
 }

 fclose($export); // close the file handle.