The "Export Feeds" module enables the use of export feeds in a user-friendly manner. In general, *export feed* is a data export template that depends on the user needs and entity data to be exported from the TreoPIM system. Learn more about the TreoPIM system and its advantages [here](https://treopim.com/help/what-is-treopim).

With the help of the "Export Feeds" module, data export from the TreoPIM system is performed in accordance with the export templates that can be further configured and customized, as well as reused at different time intervals. Export feeds can also be used to automate the data export process via Scheduled Jobs.

## Installation 

To install the "Export Feeds" module to your system, go to `Administration > Module Manager`, find this module in the "Store" list and click `Install`: 

![Export install](_assets/export-install.jpg)

Select the desired version in the installation pop-up window that appears and click the `Install` button. The module background will turn green and it will be moved to the "Installed" section of the Module Manager. Click `Run update` to confirm its installation.

Please, note that running the system update will lead to the logout of all users.

To update/remove the "Export Feeds" module from the system, use the corresponding options from its single record actions menu in `Administration > Module Manager`. 

## Administrator Functions

The "Export Feeds" module significantly extends the functionality of the [TreoPIM](https://treopim.com/help/what-is-treopim) system, so further description of the module is given in the context of TreoPIM.

After the module installation, a new `Export Feeds` configuration group is added to the TreoPIM administration page. Also there is a possibility to add export feeds as a separate navigation menu item on the `Administration > User Interface` page:

![Export feeds adding](_assets/export-feeds-add.jpg)

The "Export Feeds" module also supports transferring export feeds data of separate channels. To enable the display of the export feeds linked to separate [channels](#channel-export-via-export-feeds), add the corresponding bottom panel in the Layout Manager via its drag-and-drop:

![Layout mngr export feeds](_assets/layout-mngr-export-feeds.jpg)

### Export Feed Creation

To create a new export feed, go to the `Administration > Export Feeds` page or click `Export Feeds` in the navigation menu and then click the `Create Export Feed` button. The common creation pop-up will appear:

![Export feed creating](_assets/export-feed-create.jpg)

Here enter the export feed name, select its type and define its owner. Currently the following export feed types are supported:
 - **Simple** – designed for exporting data from any entity existing in your system with the ability to configure the list of fields to be exported, their order and names. Also this export feed type allows you to export product attribute values and product categories for the product entities in accordance with their scope levels. Refer to the **TreoPIM user guide** to learn more about the [attributes](https://treopim.com/help/attributes) and [products](https://treopim.com/help/products).
- **Product image** – designed for exporting product images existing in your system. The resulting file will contain the URL links to the exported product images arranged in accordance with the scope level (global or channel) defined for product images in the system.

Click the `Save` button to complete the operation; the new record will be added to the export feeds list. You can configure it right away on the detail view page that opens or return to it later.

### Export Feed Configuration

To configure the export feed, click the desired record in the export feeds list; the following detail view page will open:

![Export feed cfg](_assets/export-feed-cfg.jpg)

In-line editing is supported here. So prior to making any changes, you have to click the pencil icon located on the right of each editable field.

*For details on in-line editing and other features of the TreoPIM system, refer to the **Entity Records** section of the [**Views and Panels**](https://treopim.com/help/views-and-panels) article of our user guide.*

The following settings are available on the `OVERVIEW` panel:

- **Active** – select this checkbox to activate the export feed. If the export feed is not activated, the exporting feature will be disabled for it. 
- **Name** – modify the export feed name, if needed.
- **Channel** – to export the data prepared for a specific channel, please set the desired channel by using the actions button and selecting the existing channel in the `Channels` pop-up that appears. This setting is available only for products.
- **Description** – enter the description of the export feed usage, i.e. what data and in what form they are exported, as a reminder for the future. This field is not required.
- **Type** – the export feed type defined on its creation only; it cannot be modified.

#### File

The export file parameters are configured on the `FILE` panel:

![Export feed cfg file](_assets/export-feed-cfg-file.jpg)

- **File format** – from the drop-down list select the file type – CSV or XLSX (Excel) – in which the data export will be performed. 
- **Header row** – leave the checkbox activated to include the column names in the export file or remove its selection to skip column names from exporting.

When the SCV file format is selected, the following settings are added to be configured via the drop-down lists:
- **Field delimiter** – select the preferred field delimiter to be used in the export file: `;`, `,`, `\t`, `|`.
- **Text qualifier** – select the preferred separator of the values within a cell: single or double quotes.

Please, note that by default the XLSX (Excel) file format is set and the `Header row` checkbox is activated.

#### Products Filter

The TreoPIM system allows you to export a specific range of product data, which can be defined on the `PRODUCTS FILTER` panel. Here you can build filters by categories linked to the product, entity fields of the product itself, and by product attributes and their values:

![Products filter](_assets/products-filter.jpg)

You can save the created filters for their further use for exporting the product data.
 
This setting is available only for products.

Filtering settings for export feeds are the same as for product list view. However, the list of filters can be modified in the Layout Manager on the `Search filters` layout for the `products` entity:

![Layout mngr, search filters](_assets/layout-mngr-search-filters.jpg)

#### Simple Type Settings

To enable editing of the parameters on the `SIMPLE TYPE SETTINGS` panel, click the `Edit` button on the detail view page of the current `simple` export feed and configure the following settings:

![Simple type settings](_assets/simple-type-settings.jpg)

- **Entity** – from the drop-down list of entities available in the system select the desired entity type, for which this export feed will be used.
- **Field value delimiter** – enter the preferred separator of the values within a field.

#### Configurator

The configuration of the entity fields is performed on the `CONFIGURATOR` panel on either the detail or edit view page of the export feed. By default, the required fields of the entity type defined on the `SIMPLE TYPE SETTINGS` panel are displayed there, and depending on this choice, the `CONFIGURATOR` panel contains different fields. For [products](https://treopim.com/help/products), this panel looks as follows: 

![Configurator panel](_assets/configurator.jpg)

Here you can change the entity field name to be displayed in the export file by entering the desired value in the corresponding `Column` text box:

![Column renaming](_assets/column-renaming.jpg)

Changing the field order to be displayed in the export file is also done here via drag-and-drop:

![Field order changing](_assets/field-order-changing.jpg)

Use the `Remove` button to remove the desired field from the export file.

To add more entity fields for export, select the `Add entity field` option from the adding drop-down menu; the following pop-up will appear:

![Entity field adding](_assets/add-field.jpg)

Here select the field from the drop-down list of all fields available in the system for the given entity, modify the `Column` value, if needed, and define whether data export should be made by ID or code (for those fields, where this option is available). 

For the `Product categories` field there is also the ability to choose its scope level:

![Product category scope](_assets/product-category-scope.jpg)

If the `Channel` scope level is defined, also select the required channel to be used for product categories in the corresponding field. 

The "Export Feeds" module also allows you to export product attribute values. They can be added to the export feed on the `CONFIGURATOR` panel via the `Add product attribute` option from the adding drop-down menu:

![Product attribute adding](_assets/add-attribute.jpg)

In the creation pop-up that appears select the attribute from the list of the existing attributes and define its scope level – global or channel. The `Column` field is filled in automatically depending on the selected options:

![Channel attribute](_assets/attribute-channel.jpg)

Alternatively, you can enter a different `Column` name via the keyboard.

Both entity field and product attribute records added to the export feed can be edited via the corresponding option of the single record action menu:

![Attribute editing](_assets/attribute-edit.jpg)

Click the `Remove` button to delete the corresponding added entity field or product attribute record.

## Export Feed Operations and Actions

Export feed records can be duplicated and removed whenever needed.

To *duplicate* the existing export feed record, use the corresponding option from the actions menu on the desired export feed record detail view page:

![Duplicate feed](_assets/duplicate-feed.jpg)

You will be redirected to the export feed creation page and get all the values of the last chosen export feed record copied in the empty fields of the new feed record to be created. 

In order to *remove* the export feed record, use the corresponding option from the actions menu on the desired export feed record detail view page or from the single record actions menu on the export feeds list view page:

![Remove feed](_assets/remove-export-feed.jpg)

To complete the operation, click the `Remove` button in the confirmation message that appears.

The "Export Feeds" module also supports common TreoCore *mass actions* that can be applied to several selected export feed records, i.e. records with set checkboxes. These actions can be found in the corresponding menu on the export feeds list view page:

![Mass actions](_assets/mass-actions.jpg)

- **Remove** – to remove the selected export feed records (multiple deletion).
- **Merge** – to merge the selected export feed records.
- **Mass update** – to update several selected export feed records at once. To have a longer list of fields available for mass updating, please, contact your administrator.
- **Export** – to export the desired data fields of the selected export feed records in the XLSX or CSV format.

### Access Rights

To enable export feed creation, editing, usage and deletion by other users, configure the corresponding access rights to the `Export feeds` entity for the desired user / team / portal user role on the `Administration > Roles > 'Role name'` page: 

![Export role cfg](_assets/export-role-cfg.jpg)

To enable the use of export feeds from channels, configure at least reading rights for the `Channels` entity on the same page.

## User Functions 

After the "Export Feeds" module is installed and configured by the administrator, user can work with export feeds in accordance with his role rights that are predefined by the administrator.

To start exporting the data via the active export feed, click the `Export now` button on its detail view page or use the `Export now` option from the single record actions menu on the "Export feeds" list view page:

![Export now button, option](_assets/export-now-option.jpg)

When export is started, its details and current status are displayed in the Queue Manager pop-up that appears automatically:

![Queue manager](_assets/queue-manager.jpg)

After the successful data export, you can download the exported file right from the Queue Manager or close the completed export task.

### Channel Export via Export Feeds

If an active channel contains one or several export feeds linked to it, the channel export feature is enabled for it. To export data belonging to a separate channel, open the given channel and click the `Export now` button:

![Export now channel](_assets/export-now-channel.jpg)

To see the list of export feeds linked to the channel and operate with them on a separate panel, the `Export feeds` panel should be added by the [administrator](#administrator-functions), as described above. 

When the channel export is used, the data is exported into a single archive with separate files for each active export feed, linked to the given channel.

***Get the "Export Feeds" module now to export your data via highly customizable and reusable export templates!***


