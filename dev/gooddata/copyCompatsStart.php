<html>
  <head>
    <title>Copy eBay Vehicle Compatibilities</title>
  </head>
  <body>
    <form action="copyCompats2.php" type="get">
    Input a single sku, or multiple skus on separate lines: <br/>
	<textarea rows="30" cols="30" name="skus"></textarea><br/>
	<input type="radio" name="resultTo" value="ebay"/>Update eBay immediately<br/>
	<input type="radio" name="resultTo" value="file" checked=true"/>Write to CSV only<br>
    <input type="hidden" name="next" value="0">
    <input type="hidden" name="todo" value="start">
    <input type="submit" value="Submit">
    </form>
  </body>
</html>