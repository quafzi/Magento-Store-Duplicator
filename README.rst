Copy Magento StoreViews
=======================

This little script allows you to copy settings and products of StoreViews.
To copy categories I recommend an extension like Amasty_Catcopy.

Installation
------------

Copy the contents of this repository to your Magento folder.

Usage
-----

Backup your database!

Create the website, store and StoreView. Check their id (you'll find it in the backend link url of the storeView) and launch that script:

::

    php -f shell/copyStoreData.php -- --source <sourceId> --target <targetId>
