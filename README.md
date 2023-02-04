# WooCommerce Mix and Match Products - Weight Validation

### Quickstart

This is a developmental repo. Clone this repo and run `npm install && npm run build`   
OR    
|[Download latest release](https://github.com/kathyisawesome/wc-mnm-weight/releases/latest)|
|---|

### What's This?

Experimental mini-extension for [WooCommerce Mix and Match Products](https://woocommerce.com/products/woocommerce-mix-and-match-products/) that validates a container product by the _weight_ of the selected child products.

![Screenshot of front end Mix and Match product showing each product's weight and a total running weight](https://user-images.githubusercontent.com/507025/99579853-fad70f80-299b-11eb-88cd-11c50a120c91.png)

### Usage

1. Go to the Mix and Match tab in the product data metabox (if creating a new product, select Mix and Match as the product type and then go to the Mix and Match tab)
2. Change the "Validate by" option to "By weight" and then enter the minimum and maximum weights. (Weights are in the units set for your store in the WooCommerce settings)

![Screenshot of Mix and Match data tab showing additional fields for "Validate by", "min weight", and "max weight"](https://user-images.githubusercontent.com/507025/99579950-26f29080-299c-11eb-856a-719eacdb1f35.png)

### Important

1. This is provided as is and does not receive priority support.
2. Please test thoroughly before using in production.
3. Requires Mix and Match 2.4.0+

### Known Limitations
1. Min/Max container prices are still calculated by the core plugin as a function of _stock_ limitations, so that From price may not be what you'd expect by weight limitations.
2. Unsure handling of weight rounding. Will definitely work better with whole numbers or weight that will not require rounding on calculations, ex: 1kg, .5kg, .25kg.
3. You are responsible for making sure the container _can_ be filled at the weight restrictions set. Ie: a .5kg fixed container cannot be correctly filled if all items are .333kg.

### Automatic plugin updates

Plugin updates can be enabled by installing the [Git Updater](https://git-updater.com/) plugin.
