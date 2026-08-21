<?php

declare(strict_types=1);

namespace App\Support\Documentation;

final class Pharmacist
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'pos',
                'en' => 'The Point of Sale screen',
                'tl' => 'Ang Point of Sale screen',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'The catalogue is on the left, the cart on the right. The top bar holds the scanner status, <b>Price Check</b>, and the product search.',
                        'tl' => 'Nasa kaliwa ang katalogo, nasa kanan ang cart. Nasa itaas na bar ang scanner status, <b>Price Check</b>, at ang product search.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-empty.png',
                        'en' => 'The pharmacy POS before anything is added.',
                        'tl' => 'Ang pharmacy POS bago may maidagdag.',
                    ],
                    [
                        'type' => 'text',
                        'en' => 'Each product row shows its image, brand name, generic name, dosage, stock on hand, and a packaging selector when the product sells in more than one unit.',
                        'tl' => 'Ipinapakita ng bawat product row ang larawan, brand name, generic name, dosage, natitirang stock, at packaging selector kapag mahigit isang unit ang benta ng produkto.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-packaging-selector.png',
                        'en' => 'The packaging selector: pc at &#8369;5.00 or box at &#8369;500.00. The price follows the unit you choose.',
                        'tl' => 'Ang packaging selector: pc sa &#8369;5.00 o box sa &#8369;500.00. Sumusunod ang presyo sa unit na pipiliin mo.',
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => 'A <b>Rx Req.</b> badge means the item requires a prescription. Follow your own dispensing rules; the system will not stop the sale.', 'tl' => 'Ang <b>Rx Req.</b> badge ay nangangahulugang kailangan ng reseta. Sundin ang sariling patakaran ninyo sa pag-dispense; hindi pipigilan ng sistema ang benta.'],
                            ['en' => 'A greyed-out row is either out of stock or has no packaging set up. A red <b>No Packaging</b> flag must be fixed in Products before the item can be sold.', 'tl' => 'Ang naka-grey na row ay wala nang stock o walang naka-set na packaging. Kailangang ayusin sa Products ang pulang <b>No Packaging</b> flag bago maibenta ang item.'],
                            ['en' => 'Use the category strip to narrow the list, and <b>All Products</b> to clear the filter.', 'tl' => 'Gamitin ang category strip para paliitin ang listahan, at ang <b>All Products</b> para alisin ang filter.'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'selling',
                'en' => 'Making a sale',
                'tl' => 'Paggawa ng benta',
                'blocks' => [
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'Choose the customer first: leave it on <b>Walk-in</b>, or switch to <b>Customer</b> and search for one.', 'tl' => 'Piliin muna ang customer: iwan sa <b>Walk-in</b>, o lumipat sa <b>Customer</b> at maghanap.'],
                            ['en' => 'Find the item by typing in the search box, filtering by category, or scanning its barcode.', 'tl' => 'Hanapin ang item sa search box, i-filter ayon sa category, o i-scan ang barcode nito.'],
                            ['en' => 'Pick the packaging you are selling, if there is more than one.', 'tl' => 'Piliin ang packaging na ibebenta mo, kung mahigit isa ito.'],
                            ['en' => 'Click the row to add it to the cart. Click again to increase, or type the quantity directly.', 'tl' => 'I-click ang row para maidagdag sa cart. I-click ulit para dagdagan, o i-type mismo ang dami.'],
                            ['en' => 'Press <b>Checkout</b> or <kbd>F4</kbd>.', 'tl' => 'Pindutin ang <b>Checkout</b> o <kbd>F4</kbd>.'],
                            ['en' => 'Choose the payment method, enter the amount received, then <b>Confirm Payment</b>. Change is computed for you.', 'tl' => 'Piliin ang payment method, ilagay ang natanggap na halaga, tapos <b>Confirm Payment</b>. Kusang kinakalkula ang sukli.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-customer-mode.png',
                        'en' => 'Switching the cart from Walk-in to Customer.',
                        'tl' => 'Paglipat ng cart mula Walk-in patungong Customer.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-payment-pwd.png',
                        'en' => 'Process Payment for a PWD customer: the 20% discount is applied automatically and shown as Includes 20% off.',
                        'tl' => 'Process Payment para sa PWD customer: kusang na-apply ang 20% discount at ipinapakita bilang Includes 20% off.',
                    ],
                    [
                        'type' => 'warn',
                        'en' => 'Some payment methods require a reference number. The system will not let you finish without it.',
                        'tl' => 'May ilang payment method na kailangan ng reference number. Hindi ka nito papayagang matapos kung wala nito.',
                    ],
                ],
            ],
            [
                'id' => 'price-check',
                'en' => 'Price Check (F2)',
                'tl' => 'Price Check (F2)',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'A customer asks the price of something while you already have a half-built cart. Use Price Check &mdash; it only reads, so it cannot disturb the sale in progress.',
                        'tl' => 'May nagtanong ng presyo habang may kalahating cart ka na. Gamitin ang Price Check &mdash; nagbabasa lang ito, kaya hindi nito magugulo ang kasalukuyang benta.',
                    ],
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'Press <kbd>F2</kbd> or click <b>Price Check</b>.', 'tl' => 'Pindutin ang <kbd>F2</kbd> o i-click ang <b>Price Check</b>.'],
                            ['en' => 'Scan the item, or type a name or product code and press <kbd>Enter</kbd>.', 'tl' => 'I-scan ang item, o i-type ang pangalan o product code tapos pindutin ang <kbd>Enter</kbd>.'],
                            ['en' => 'You get stock on hand, every packaging, the regular price and any partner prices.', 'tl' => 'Makikita mo ang natitirang stock, lahat ng packaging, ang regular price at ang mga partner price.'],
                            ['en' => 'Close it. Your cart is exactly as you left it.', 'tl' => 'Isara ito. Buo pa rin ang cart mo gaya ng iniwan mo.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-price-check.png',
                        'en' => 'Price Check open over the POS. While it is open, scanning looks the item up instead of adding it to the cart.',
                        'tl' => 'Bukas ang Price Check sa ibabaw ng POS. Habang bukas ito, ang pag-scan ay naghahanap ng item sa halip na idagdag ito sa cart.',
                    ],
                ],
            ],
            [
                'id' => 'dashboard',
                'en' => 'Dashboard',
                'tl' => 'Dashboard',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Your morning check. Six counters across the top: total products, total items on hand, low stock, expiring soon, out of stock, and already expired.',
                        'tl' => 'Ang tsek mo tuwing umaga. Anim na counter sa itaas: total products, total items on hand, low stock, expiring soon, out of stock, at already expired.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'dashboard.png',
                        'en' => 'The pharmacy dashboard, with the sales trend and a critical expiry panel.',
                        'tl' => 'Ang pharmacy dashboard, may sales trend at critical expiry panel.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'dashboard-lowstock-expired.png',
                        'en' => 'Low Stock Alerts and Expired Batches, each exportable to Excel.',
                        'tl' => 'Low Stock Alerts at Expired Batches, pwedeng i-export sa Excel.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'dashboard-demand-movements.png',
                        'en' => 'Top In-Demand Products and the Stock Movements ledger, filterable by type and date.',
                        'tl' => 'Top In-Demand Products at ang Stock Movements ledger, pwedeng i-filter ayon sa type at petsa.',
                    ],
                ],
            ],
            [
                'id' => 'products',
                'en' => 'Products',
                'tl' => 'Products',
                'blocks' => [
                    [
                        'type' => 'image',
                        'src' => 'products-list.png',
                        'en' => 'The product list, with counters, filters, and per-row actions for adjusting stock, editing, and enabling or disabling.',
                        'tl' => 'Ang listahan ng produkto, may counters, filters, at aksyon kada row para sa pag-adjust ng stock, pag-edit, at pag-enable o disable.',
                    ],
                    [
                        'type' => 'text',
                        'en' => '<b>Adding a product.</b> Click <b>Add Product</b> and fill in the two panels: product information on the left, and selling units on the right.',
                        'tl' => '<b>Pagdagdag ng produkto.</b> I-click ang <b>Add Product</b> at punan ang dalawang panel: impormasyon ng produkto sa kaliwa, at mga selling unit sa kanan.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'products-create.png',
                        'en' => 'Create New Product. The image is optional &mdash; square works best, ideal 800x800px, up to 2MB.',
                        'tl' => 'Create New Product. Opsyonal ang larawan &mdash; mas maganda ang square, ideal 800x800px, hanggang 2MB.',
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => '<b>Base Selling Unit</b> is the smallest unit you sell, for example Piece or Tablet. Stock is counted in this unit and its conversion factor is always 1.', 'tl' => 'Ang <b>Base Selling Unit</b> ay ang pinakamaliit na unit na binebenta mo, halimbawa Piece o Tablet. Dito binibilang ang stock at laging 1 ang conversion factor nito.'],
                            ['en' => '<b>Larger Packs</b> are bulk units like a Box. If a box holds 100 pieces, its conversion factor is 100.', 'tl' => 'Ang <b>Larger Packs</b> ay mga bulk unit tulad ng Box. Kung 100 piraso ang laman ng isang box, 100 ang conversion factor nito.'],
                            ['en' => 'Each packaging can carry its own <b>barcode</b>, so a box and a single piece scan to the right price.', 'tl' => 'May sariling <b>barcode</b> ang bawat packaging, para tama ang presyong lalabas kapag na-scan ang box o ang isang piraso.'],
                            ['en' => 'Press <b>Generate</b> if you want the system to invent a product code for you.', 'tl' => 'Pindutin ang <b>Generate</b> kung gusto mong ang sistema ang gumawa ng product code.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'src' => 'products-create-inventory.png',
                        'en' => 'The Inventory panel: stock type, batch number, low stock alert level, opening quantity, cost and expiration date.',
                        'tl' => 'Ang Inventory panel: stock type, batch number, low stock alert level, panimulang dami, halaga at expiration date.',
                    ],
                    [
                        'type' => 'note',
                        'en' => 'Set <b>Stock Type</b> to <b>Special Order</b> for items you never keep on the shelf. Those can be sold with zero stock, and the system raises the purchase order to the supplier for you.',
                        'tl' => 'I-set ang <b>Stock Type</b> sa <b>Special Order</b> para sa mga item na hindi mo talaga iniistock. Pwedeng ibenta ang mga ito kahit walang stock, at kusang gagawa ang sistema ng purchase order sa supplier.',
                    ],
                    [
                        'type' => 'text',
                        'en' => '<b>Editing a product</b> gives you two tabs: <b>1. Product &amp; Packagings</b> for the details and prices, and <b>2. Partnership Pricing</b> for mandated partner prices.',
                        'tl' => 'Sa <b>pag-edit ng produkto</b>, may dalawang tab: <b>1. Product &amp; Packagings</b> para sa detalye at presyo, at <b>2. Partnership Pricing</b> para sa mandated na presyo ng partner.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'products-edit.png',
                        'en' => 'Edit Product, with an uploaded image and a Remove image button.',
                        'tl' => 'Edit Product, may na-upload na larawan at Remove image button.',
                    ],
                    [
                        'type' => 'text',
                        'en' => '<b>Scan / Look up</b> (or <kbd>F2</kbd>) finds a product by barcode or name and shows its stock and prices, with a button to jump straight to editing it.',
                        'tl' => 'Ang <b>Scan / Look up</b> (o <kbd>F2</kbd>) ay naghahanap ng produkto sa barcode o pangalan at ipinapakita ang stock at presyo nito, may button para dumiretso sa pag-edit.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'products-lookup.png',
                        'en' => 'Product Lookup on the Products page.',
                        'tl' => 'Product Lookup sa Products page.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'products-import.png',
                        'en' => 'Import Products: download the template, fill it in, then upload it back.',
                        'tl' => 'Import Products: i-download ang template, punan ito, tapos i-upload pabalik.',
                    ],
                    [
                        'type' => 'text',
                        'en' => '<b>Import Products</b> creates many products at once from a spreadsheet. Download the template first &mdash; it explains how to group packagings using the same product code.',
                        'tl' => 'Ang <b>Import Products</b> ay gumagawa ng maraming produkto nang sabay mula sa spreadsheet. I-download muna ang template &mdash; ipinapaliwanag nito kung paano pagsama-samahin ang packagings gamit ang iisang product code.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'products-import-format.png',
                        'en' => 'The import format guide, including the note that partnership prices are configured after importing, not in the file.',
                        'tl' => 'Ang gabay sa import format, kasama ang paalala na ang partnership prices ay ini-set pagkatapos mag-import, hindi sa file.',
                    ],
                ],
            ],
            [
                'id' => 'stocks',
                'en' => 'Stocks and expiry',
                'tl' => 'Stocks at expiry',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Stocks shows physical batches rather than products. Tabs across the top separate <b>Active Stocks</b>, <b>Expiring Soon</b>, <b>Expired</b> and <b>Empty / Depleted Batches</b>.',
                        'tl' => 'Ipinapakita ng Stocks ang mga aktwal na batch, hindi ang produkto. Ang mga tab sa itaas ay naghihiwalay ng <b>Active Stocks</b>, <b>Expiring Soon</b>, <b>Expired</b> at <b>Empty / Depleted Batches</b>.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'stocks.png',
                        'en' => 'Pharmacy Stocks, with total asset value and a critical expiry counter. Expiry dates near their limit are flagged in orange.',
                        'tl' => 'Pharmacy Stocks, may total asset value at critical expiry counter. Naka-orange ang mga expiry date na malapit nang mag-expire.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'stocks-edit-batch.png',
                        'en' => 'Edit Batch Record: correct the batch number, expiry date, actual quantity or unit cost.',
                        'tl' => 'Edit Batch Record: itama ang batch number, expiry date, aktwal na dami o unit cost.',
                    ],
                    [
                        'type' => 'text',
                        'en' => '<b>Adjusting stock</b> is done from the Products list, using the stock icon on the row.',
                        'tl' => 'Ang <b>pag-adjust ng stock</b> ay ginagawa mula sa Products list, gamit ang stock icon sa row.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'adjust-stock-remove.png',
                        'en' => 'Remove Stock requires choosing the exact batch, so expiry and cost stay accurate.',
                        'tl' => 'Sa Remove Stock, kailangang piliin ang tamang batch, para manatiling tama ang expiry at cost.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'adjust-stock-add.png',
                        'en' => 'Add Stock creates a new batch, so it asks for a batch number and expiry date.',
                        'tl' => 'Ang Add Stock ay gumagawa ng bagong batch, kaya humihingi ito ng batch number at expiry date.',
                    ],
                    [
                        'type' => 'warn',
                        'en' => 'Stock added by adjustment is recorded at zero cost, which makes profit look better than it is. For anything you actually bought, record a purchase instead.',
                        'tl' => 'Ang stock na idinagdag sa pamamagitan ng adjustment ay walang naitalang cost, kaya mas mukhang malaki ang kita kaysa totoo. Para sa mga talagang binili mo, mag-record ng purchase sa halip.',
                    ],
                    [
                        'type' => 'warn',
                        'en' => 'The counter does not block an expired batch, and it sells the oldest received batch first rather than the soonest to expire. Check this page regularly and pull expired stock off the shelf.',
                        'tl' => 'Hindi hinaharang ng counter ang expired na batch, at ang pinakamatagal nang natanggap ang unang naibebenta, hindi ang pinakamalapit nang mag-expire. Regular na tingnan ang page na ito at alisin sa istante ang expired na stock.',
                    ],
                ],
            ],
            [
                'id' => 'purchases',
                'en' => 'Purchases',
                'tl' => 'Purchases',
                'blocks' => [
                    [
                        'type' => 'image',
                        'src' => 'purchases.png',
                        'en' => 'Purchase Orders, with counters and tabs for All, Pending, Completed and Cancelled.',
                        'tl' => 'Purchase Orders, may counters at tabs para sa All, Pending, Completed at Cancelled.',
                    ],
                    [
                        'type' => 'text',
                        'en' => '<b>Ordering.</b> Click <b>New Purchase Order</b>, choose the supplier, add the products and quantities, then submit. The order sits as Pending with a PO number until the goods arrive.',
                        'tl' => '<b>Pag-order.</b> I-click ang <b>New Purchase Order</b>, piliin ang supplier, idagdag ang mga produkto at dami, tapos i-submit. Mananatili itong Pending na may PO number hanggang dumating ang paninda.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'purchases-create.png',
                        'en' => 'New Purchase Order.',
                        'tl' => 'New Purchase Order.',
                    ],
                    [
                        'type' => 'text',
                        'en' => '<b>Receiving.</b> Click <b>Record Receiving</b>. You can receive against an existing PO, or record a direct purchase you made without a PO.',
                        'tl' => '<b>Pagtanggap.</b> I-click ang <b>Record Receiving</b>. Pwede kang tumanggap laban sa umiiral na PO, o mag-record ng direktang bili na walang PO.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'purchases-receive-po.png',
                        'en' => 'Receiving against a pending purchase order.',
                        'tl' => 'Pagtanggap laban sa nakabinbing purchase order.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'purchases-receive-direct.png',
                        'en' => 'Direct Receiving: enter what actually arrived, including actual cost, batch number and expiry.',
                        'tl' => 'Direct Receiving: ilagay ang aktwal na dumating, kasama ang aktwal na halaga, batch number at expiry.',
                    ],
                    [
                        'type' => 'warn',
                        'en' => 'Enter what <b>actually</b> arrived from the invoice, not what you ordered. Receiving marks the whole order Completed, and a completed order cannot be received again &mdash; for a partial delivery, record what came in and raise a new order for the balance.',
                        'tl' => 'Ilagay ang <b>aktwal</b> na dumating base sa invoice, hindi ang inorder mo. Kapag natanggap, buong order ang nagiging Completed, at hindi na ito pwedeng tanggapin ulit &mdash; kung parsyal ang delivery, itala ang dumating at gumawa ng bagong order para sa natitira.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'purchases-view.png',
                        'en' => 'Viewing a completed order: quantity ordered against quantity received, and the actual total cost.',
                        'tl' => 'Pagtingin sa natapos na order: dami ng inorder laban sa dami ng natanggap, at ang aktwal na kabuuang halaga.',
                    ],
                ],
            ],
            [
                'id' => 'sales',
                'en' => 'Sales history',
                'tl' => 'Kasaysayan ng benta',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Date tabs run across the top &mdash; Today, Yesterday, Last 7 Days, Last 30 Days and All Time. Everything on the page follows the tab you pick.',
                        'tl' => 'Nasa itaas ang mga date tab &mdash; Today, Yesterday, Last 7 Days, Last 30 Days at All Time. Sumusunod ang lahat sa page sa tab na pipiliin mo.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'sales.png',
                        'en' => 'Sales, with top demand products, four counters, and the transaction table.',
                        'tl' => 'Sales, may top demand products, apat na counter, at ang talahanayan ng transaksyon.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'sales-detail-regular.png',
                        'en' => 'Clicking View opens the full sale, including a SOURCE tag on each line and a Print Receipt button.',
                        'tl' => 'Kapag nag-click ng View, bubukas ang buong sale, kasama ang SOURCE tag sa bawat linya at ang Print Receipt button.',
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => '<b>Export Ledger</b> downloads the raw transaction list.', 'tl' => 'Ang <b>Export Ledger</b> ay nagda-download ng listahan ng transaksyon.'],
                            ['en' => '<b>Sales Report</b> gives a formatted report for a date range you choose.', 'tl' => 'Ang <b>Sales Report</b> ay nagbibigay ng pormal na report para sa piniling saklaw ng petsa.'],
                            ['en' => '<b>Daily Report</b> is the end-of-day summary.', 'tl' => 'Ang <b>Daily Report</b> ay ang buod sa pagtatapos ng araw.'],
                            ['en' => 'Below the table, the <b>Partnership Sales</b> panel appears when your branch uses partner pricing.', 'tl' => 'Sa ilalim ng talahanayan, lumalabas ang <b>Partnership Sales</b> panel kapag gumagamit ng partner pricing ang branch mo.'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'expenses-reports',
                'en' => 'Expenses and Reports',
                'tl' => 'Expenses at Reports',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Record operating costs under <b>Expenses</b> so net profit is real. Categories are Rent, Utilities, Payroll, Supplies, Maintenance, Marketing, Taxes and Other.',
                        'tl' => 'Itala ang mga gastos sa operasyon sa <b>Expenses</b> para totoo ang net profit. Ang mga category ay Rent, Utilities, Payroll, Supplies, Maintenance, Marketing, Taxes at Other.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'expenses.png',
                        'en' => 'The Expenses page: totals for the period on top, then every recorded expense.',
                        'tl' => 'Ang Expenses page: nasa itaas ang kabuuan para sa panahong iyon, tapos ang bawat naitalang gastos.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'expenses-create.png',
                        'en' => 'Record New Expense: amount, date, category, optional reference number and description.',
                        'tl' => 'Record New Expense: halaga, petsa, category, opsyonal na reference number at deskripsyon.',
                    ],
                    [
                        'type' => 'note',
                        'en' => 'Gross profit is revenue minus product cost. Net profit also subtracts these expenses. Skip this page and every net profit figure in your reports is overstated.',
                        'tl' => 'Ang gross profit ay kita bawas ang halaga ng produkto. Ang net profit ay binabawasan pa ng mga gastos na ito. Kapag nilaktawan mo ang page na ito, sobra ang lalabas na net profit sa mga report mo.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'reports.png',
                        'en' => 'Business Reports: revenue, expenses, gross and net profit with margins, plus the revenue versus expenses trend.',
                        'tl' => 'Business Reports: kita, gastos, gross at net profit kasama ang margin, at ang trend ng kita laban sa gastos.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'reports-stock-movement.png',
                        'en' => 'The Stock Movement Report, showing every stock in and out with running balance and who did it.',
                        'tl' => 'Ang Stock Movement Report, ipinapakita ang bawat pasok at labas ng stock kasama ang running balance at kung sino ang gumawa.',
                    ],
                ],
            ],
        ];
    }
}
