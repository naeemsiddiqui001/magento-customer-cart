#Polywood_ViewCustomerCart
Polywood_ViewCustomerCart module functionality is represented by the following sub-systems:
 - Showing the quote id to the customer on the left top corner of every page.
 - Provides a functionality on the admin side to display all the products for a particular quote along with totals.

 ##Services

``app/code/Polywood/ViewCustomerCart/etc/adminhtml/menu.xml`` is responsible to display the ``View Customer Cart`` submenu in the Customer menu on the backend side.

``Polywood\ViewCustomerCart\ViewModel\GetQuote`` is responsible to return the current quote id of the customer to be displayed to the customer(s) on the left top corner of the page header.

``Polywood\ViewCustomerCart\ViewModel\QuoteInformation`` is responsible to return the details of a quote to be displayed on the backend side.
