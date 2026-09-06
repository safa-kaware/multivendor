<?php

require_once __DIR__ . "/session.php";
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/../functions/helpers.php";
require_once __DIR__ . "/../functions/auth_functions.php";

define("APP_NAME", "MultiVendor");

define(
    "BASE_URL",
    "http://lily.wuaze.com/"
);

/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE URL
|--------------------------------------------------------------------------
|
| Database stores only the filename:
|
| product_12345.jpg
|
| Physical file:
|
| C:\xampp\htdocs\multivendor\uploads\products\product_12345.jpg
|
*/
if (!function_exists("categoryImageUrl")) {

    function categoryImageUrl($filename)
    {
        if (empty($filename)) {
            return "";
        }

        $filename = trim($filename);

        if (
            filter_var(
                $filename,
                FILTER_VALIDATE_URL
            )
        ) {
            return $filename;
        }

        return BASE_URL
            . "uploads/categories/"
            . rawurlencode($filename);
    }
}

if (!function_exists("productImageUrl")) {

    function productImageUrl($filename)
    {
        if (empty($filename)) {
            return "";
        }
        

        /*
        | Remove accidental spaces
        */
        $filename = trim($filename);

        /*
        | If old products contain a full external URL,
        | keep it unchanged.
        */
        if (
            filter_var(
                $filename,
                FILTER_VALIDATE_URL
            )
        ) {
            return $filename;
        }

        /*
        | Convert filename into a safe URL
        */
        return BASE_URL
            . "uploads/products/"
            . rawurlencode($filename);
  
  
            }

            
}

