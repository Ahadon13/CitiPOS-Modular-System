<?php

declare(strict_types=1);

namespace App\Support\Documentation;

/**
 * Partnership pricing, end to end: customer type, customer, price book, the
 * counter, the receipt and the reports.
 */
final class Partnership
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'what',
                'en' => 'What partnership pricing is',
                'tl' => 'Ano ang partnership pricing',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Some customers &mdash; LGU, DSWD and similar &mdash; have a mandated price for certain products that is different from your normal retail price. Partnership pricing lets you record that agreed price once, and the counter then charges it automatically whenever that customer buys.',
                        'tl' => 'May mga customer &mdash; LGU, DSWD at katulad &mdash; na may mandated na presyo sa ilang produkto, iba sa normal mong retail price. Sa partnership pricing, isang beses mo lang itala ang napagkasunduang presyo, at awtomatiko na itong sisingilin ng counter tuwing bibili ang customer na iyon.',
                    ],
                    [
                        'type' => 'warn',
                        'en' => 'There are two different mechanisms, and it matters which one you use. A <b>customer type discount</b> is a percentage off everything (this is how PWD and Senior Citizen 20% works). A <b>partnership price</b> is a fixed peso amount for one specific packaging of one specific product (this is how LGU and DSWD mandated prices work).',
                        'tl' => 'May dalawang magkaibang paraan, at mahalaga kung alin ang gagamitin. Ang <b>customer type discount</b> ay porsyento na bawas sa lahat (ganito gumagana ang 20% ng PWD at Senior Citizen). Ang <b>partnership price</b> ay nakatakdang halaga sa piso para sa isang tiyak na packaging ng isang tiyak na produkto (ganito ang mandated na presyo ng LGU at DSWD).',
                    ],
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => '', 'tl' => ''],
                            ['en' => 'Customer type discount', 'tl' => 'Customer type discount'],
                            ['en' => 'Partnership price', 'tl' => 'Partnership price'],
                        ],
                        'rows' => [
                            [
                                ['en' => 'What you set', 'tl' => 'Ano ang ini-set'],
                                ['en' => 'A percentage, e.g. 20%', 'tl' => 'Porsyento, hal. 20%'],
                                ['en' => 'A peso price, e.g. &#8369;3.00', 'tl' => 'Presyo sa piso, hal. &#8369;3.00'],
                            ],
                            [
                                ['en' => 'Applies to', 'tl' => 'Saan ito applicable'],
                                ['en' => 'Every product in the cart', 'tl' => 'Lahat ng produkto sa cart'],
                                ['en' => 'One packaging of one product', 'tl' => 'Isang packaging ng isang produkto'],
                            ],
                            [
                                ['en' => 'Set where', 'tl' => 'Saan ini-set'],
                                ['en' => 'Customers &rarr; Customer Types', 'tl' => 'Customers &rarr; Customer Types'],
                                ['en' => 'Products &rarr; Partnership Pricing', 'tl' => 'Products &rarr; Partnership Pricing'],
                            ],
                            [
                                ['en' => 'Typical use', 'tl' => 'Karaniwang gamit'],
                                ['en' => 'PWD, Senior Citizen', 'tl' => 'PWD, Senior Citizen'],
                                ['en' => 'LGU, DSWD', 'tl' => 'LGU, DSWD'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'customer-type',
                'en' => 'Step 1 — Create the customer type',
                'tl' => 'Hakbang 1 — Gumawa ng customer type',
                'blocks' => [
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'Go to <b>Customers</b> and click <b>Customer Types</b>.', 'tl' => 'Pumunta sa <b>Customers</b> at i-click ang <b>Customer Types</b>.'],
                            ['en' => 'Type the name, for example <b>LGU</b> or <b>DSWD</b>.', 'tl' => 'I-type ang pangalan, halimbawa <b>LGU</b> o <b>DSWD</b>.'],
                            ['en' => 'Set the <b>Discount Percentage</b>. For LGU and DSWD leave it at <b>0</b>, because their prices are fixed per product, not a percentage. For PWD and Senior Citizen set <b>20</b>.', 'tl' => 'I-set ang <b>Discount Percentage</b>. Para sa LGU at DSWD, iwan itong <b>0</b>, dahil naka-fix ang presyo nila kada produkto, hindi porsyento. Para sa PWD at Senior Citizen, ilagay ang <b>20</b>.'],
                            ['en' => 'Click <b>Save Type</b>.', 'tl' => 'I-click ang <b>Save Type</b>.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'src' => 'customer-types.png',
                        'en' => 'Manage Customer Types. Note DSWD and LGU sit at 0% because their pricing is per product, while PWD and Senior Citizen carry a 20% blanket discount.',
                        'tl' => 'Manage Customer Types. Pansinin na 0% ang DSWD at LGU dahil per produkto ang presyo nila, samantalang may 20% na blanket discount ang PWD at Senior Citizen.',
                    ],
                    [
                        'type' => 'note',
                        'en' => 'The <b>Users</b> column tells you how many customers are on each type. A type still in use cannot be deleted.',
                        'tl' => 'Ipinapakita ng <b>Users</b> column kung ilang customer ang nasa bawat type. Hindi pwedeng burahin ang type na ginagamit pa.',
                    ],
                ],
            ],
            [
                'id' => 'customer',
                'en' => 'Step 2 — Create the customer',
                'tl' => 'Hakbang 2 — Gumawa ng customer',
                'blocks' => [
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'On <b>Customers</b>, click <b>Add Customer</b>.', 'tl' => 'Sa <b>Customers</b>, i-click ang <b>Add Customer</b>.'],
                            ['en' => 'Enter the <b>Full Name</b>.', 'tl' => 'Ilagay ang <b>Full Name</b>.'],
                            ['en' => 'Choose the <b>Customer Type</b> you made in step 1 &mdash; this is the field that makes partnership pricing work.', 'tl' => 'Piliin ang <b>Customer Type</b> na ginawa mo sa hakbang 1 &mdash; ito ang field na nagpapagana sa partnership pricing.'],
                            ['en' => 'Optionally record the ID card number and booklet number, which you will need for PWD and Senior Citizen claims.', 'tl' => 'Pwede ring itala ang ID card number at booklet number, na kakailanganin sa mga claim ng PWD at Senior Citizen.'],
                            ['en' => 'Click <b>Create Customer</b>.', 'tl' => 'I-click ang <b>Create Customer</b>.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'src' => 'customers-create.png',
                        'en' => 'Add New Customer. Customer Type is required, and it decides which prices this customer gets.',
                        'tl' => 'Add New Customer. Kailangan ang Customer Type, at ito ang magdedesisyon kung anong presyo ang makukuha ng customer na ito.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'customers.png',
                        'en' => 'The customer list shows each customer with their type as a badge.',
                        'tl' => 'Ipinapakita ng listahan ng customer ang bawat isa kasama ang type nila bilang badge.',
                    ],
                ],
            ],
            [
                'id' => 'price',
                'en' => 'Step 3 — Set the partnership price',
                'tl' => 'Hakbang 3 — I-set ang partnership price',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Prices are set per branch and per packaging. Open <b>Products</b>, edit the product, and go to the <b>2. Partnership Pricing</b> tab.',
                        'tl' => 'Ang presyo ay naka-set kada branch at kada packaging. Buksan ang <b>Products</b>, i-edit ang produkto, at pumunta sa <b>2. Partnership Pricing</b> tab.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'products-partnership-tab.png',
                        'en' => 'The Partnership Pricing tab, with a price box for every customer type on this packaging.',
                        'tl' => 'Ang Partnership Pricing tab, may price box para sa bawat customer type sa packaging na ito.',
                    ],
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'Find the packaging you are pricing. Each packaging has its own block, showing its regular retail price on the right.', 'tl' => 'Hanapin ang packaging na pinepresyuhan mo. May sariling block ang bawat packaging, nakalagay sa kanan ang regular retail price nito.'],
                            ['en' => 'Type the mandated price into the box for that customer type, for example <b>LGU</b>.', 'tl' => 'I-type ang mandated na presyo sa box ng customer type na iyon, halimbawa <b>LGU</b>.'],
                            ['en' => 'Leave a box <b>blank</b> if that type should just pay the regular price.', 'tl' => 'Iwanang <b>blangko</b> ang box kung dapat regular price lang ang bayaran ng type na iyon.'],
                            ['en' => 'Click <b>Save Price Book</b>.', 'tl' => 'I-click ang <b>Save Price Book</b>.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'src' => 'products-partnership-filled.png',
                        'en' => 'Filled in: DSWD and LGU are both priced at &#8369;3.00 for a Piece whose retail price is &#8369;5.00.',
                        'tl' => 'Napunan na: DSWD at LGU ay parehong &#8369;3.00 para sa Piece na ang retail price ay &#8369;5.00.',
                    ],
                    [
                        'type' => 'warn',
                        'en' => 'Prices are locked to a <b>branch</b> and a <b>packaging</b>. A price you set in one branch does not apply to another, and a price on a Piece does not apply to a Box. Set each one you actually sell.',
                        'tl' => 'Ang presyo ay nakatali sa <b>branch</b> at sa <b>packaging</b>. Hindi mo magagamit sa ibang branch ang presyong ini-set mo sa isang branch, at hindi rin masusunod sa Box ang presyo ng Piece. I-set lahat ng talagang binebenta mo.',
                    ],
                ],
            ],
            [
                'id' => 'bulk',
                'en' => 'Setting many prices at once',
                'tl' => 'Pag-set ng maraming presyo nang sabay',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'When a new mandated price list arrives, use <b>Products &rarr; Bulk Pricing</b> instead of opening each product one by one.',
                        'tl' => 'Kapag may dumating na bagong listahan ng mandated na presyo, gamitin ang <b>Products &rarr; Bulk Pricing</b> imbes na buksan isa-isa ang bawat produkto.',
                    ],
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'Filter by category, or search by generic or brand name.', 'tl' => 'I-filter ayon sa category, o mag-search gamit ang generic o brand name.'],
                            ['en' => 'Tick the checkbox on every row you want to price.', 'tl' => 'I-tsek ang checkbox sa bawat row na gusto mong presyuhan.'],
                            ['en' => 'Type the price into the box at the top for the customer type you are setting.', 'tl' => 'I-type ang presyo sa box sa itaas para sa customer type na ini-set mo.'],
                            ['en' => 'Click <b>Apply to Selected</b>.', 'tl' => 'I-click ang <b>Apply to Selected</b>.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'src' => 'bulk-pricing-filled.png',
                        'en' => 'Bulk Partnership Pricing. The table shows the retail price next to every partner price, per packaging, so you can check the whole book at a glance.',
                        'tl' => 'Bulk Partnership Pricing. Ipinapakita ng table ang retail price katabi ng bawat partner price, kada packaging, para makita mo ang buong price book nang sabay-sabay.',
                    ],
                ],
            ],
            [
                'id' => 'selling',
                'en' => 'Step 4 — Selling at a partner price',
                'tl' => 'Hakbang 4 — Pagbenta sa partner price',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'At the counter you do not apply anything by hand. You only have to pick the right customer.',
                        'tl' => 'Sa counter, wala kang kailangang i-apply nang manu-mano. Kailangan mo lang piliin ang tamang customer.',
                    ],
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'In the <b>Current Order</b> panel, switch from <b>Walk-in</b> to <b>Customer</b>.', 'tl' => 'Sa <b>Current Order</b> panel, lumipat mula <b>Walk-in</b> patungong <b>Customer</b>.'],
                            ['en' => 'Search and select the customer. The dropdown shows their type and discount, e.g. <b>LGU (LGU - 0.00%)</b>.', 'tl' => 'Hanapin at piliin ang customer. Ipinapakita ng dropdown ang type at discount nila, hal. <b>LGU (LGU - 0.00%)</b>.'],
                            ['en' => 'Add the products. Rows with a partner price for this customer now show an <b>LGU Partner</b> badge.', 'tl' => 'Idagdag ang mga produkto. Ang mga row na may partner price para sa customer na ito ay may <b>LGU Partner</b> badge na ngayon.'],
                            ['en' => 'Check the cart line: it shows the walk-in price and the partner price side by side.', 'tl' => 'Tingnan ang linya sa cart: makikita ang walk-in price at partner price na magkatabi.'],
                            ['en' => 'Take payment as normal.', 'tl' => 'Tanggapin ang bayad gaya ng dati.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-customer-dropdown.png',
                        'en' => 'Choosing the customer. Each entry shows the customer type and its discount percentage.',
                        'tl' => 'Pagpili ng customer. Ipinapakita ng bawat entry ang customer type at ang porsyento ng discount nito.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-row-lgu.png',
                        'en' => 'The product row gains an LGU Partner badge once an LGU customer is selected.',
                        'tl' => 'Lumalabas ang LGU Partner badge sa product row kapag napili na ang LGU customer.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-cart-lgu.png',
                        'en' => 'The cart line shows Walk-in &#8369;5.00 against Partner &#8369;3.00, tagged LGU Price. The total uses the partner price.',
                        'tl' => 'Ipinapakita ng cart line ang Walk-in &#8369;5.00 laban sa Partner &#8369;3.00, may tag na LGU Price. Ang total ay gumagamit ng partner price.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-payment-lgu.png',
                        'en' => 'At payment, the order summary spells out the LGU price and the walk-in price it replaced.',
                        'tl' => 'Sa pagbayad, malinaw na nakalagay sa order summary ang LGU price at ang walk-in price na pinalitan nito.',
                    ],
                    [
                        'type' => 'warn',
                        'en' => 'Select the customer <b>before</b> you build the cart. Changing the customer re-prices every line, so doing it last will change a total you may have already quoted.',
                        'tl' => 'Piliin ang customer <b>bago</b> ka mag-add ng items. Kapag pinalitan mo ang customer, nagbabago ang presyo ng lahat ng linya, kaya kung huli mo gagawin, magbabago ang total na baka nasabi mo na sa customer.',
                    ],
                ],
            ],
            [
                'id' => 'receipt',
                'en' => 'What the receipt shows',
                'tl' => 'Ano ang nakalagay sa resibo',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'The receipt prints both prices, so the customer and any auditor can see exactly what was granted.',
                        'tl' => 'Nakalimbag sa resibo ang parehong presyo, para makita ng customer at ng auditor kung ano talaga ang ibinigay.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'receipt-lgu.png',
                        'en' => 'A partnership sale: the line reads Regular PHP 5.00 / Partnership PHP 3.00 (LGU).',
                        'tl' => 'Isang partnership sale: nakasulat sa linya ang Regular PHP 5.00 / Partnership PHP 3.00 (LGU).',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'receipt-pwd.png',
                        'en' => 'For comparison, a percentage discount instead: subtotal PHP 5.00, discount -PHP 1.00, discount type PWD (20.00%).',
                        'tl' => 'Bilang paghahambing, isang porsyentong discount naman: subtotal PHP 5.00, discount -PHP 1.00, discount type PWD (20.00%).',
                    ],
                ],
            ],
            [
                'id' => 'reports',
                'en' => 'Step 5 — Tracking partnership sales',
                'tl' => 'Hakbang 5 — Pagsubaybay sa partnership sales',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Every partner-priced line is stored with both the price charged and the regular price it replaced. That is what makes the reporting possible, and it stays correct even if you change the price book later.',
                        'tl' => 'Ang bawat linyang may partner price ay naitatala kasama ang presyong siningil at ang regular na presyong pinalitan nito. Ito ang dahilan kung bakit posible ang reporting, at mananatili itong tama kahit palitan mo ang price book mamaya.',
                    ],
                    [
                        'type' => 'text',
                        'en' => 'Open <b>Sales</b> and scroll to the <b>Partnership Sales</b> panel.',
                        'tl' => 'Buksan ang <b>Sales</b> at mag-scroll pababa sa <b>Partnership Sales</b> panel.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'partnership-sales-panel.png',
                        'en' => 'Partnership Sales: partner revenue, what it would have been at regular price, the savings you gave, and every line in detail. Exports to Excel and PDF.',
                        'tl' => 'Partnership Sales: kita mula sa partner, kung magkano sana ito sa regular price, ang savings na ibinigay mo, at bawat linya nang detalyado. Pwedeng i-export sa Excel at PDF.',
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => '<b>Partner Revenue</b> &mdash; what partners actually paid.', 'tl' => '<b>Partner Revenue</b> &mdash; kung magkano talaga ang binayad ng mga partner.'],
                            ['en' => '<b>At Regular Price</b> &mdash; what the same items would have cost a walk-in.', 'tl' => '<b>At Regular Price</b> &mdash; kung magkano sana ang mga ito sa walk-in.'],
                            ['en' => '<b>Savings Given</b> &mdash; the difference, which is the subsidy you carried.', 'tl' => '<b>Savings Given</b> &mdash; ang pagkakaiba, ito ang subsidy na sinagot mo.'],
                            ['en' => '<b>Partners / Lines</b> &mdash; how many partners bought, and how many lines in total.', 'tl' => '<b>Partners / Lines</b> &mdash; ilang partner ang bumili, at ilang linya lahat.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'src' => 'sales-detail-lgu.png',
                        'en' => 'Opening a sale shows a SOURCE tag on each line, so you can see at a glance which lines were sold at a partner price.',
                        'tl' => 'Kapag binuksan ang isang sale, may SOURCE tag sa bawat linya, kaya agad mong makikita kung aling linya ang naibenta sa partner price.',
                    ],
                    [
                        'type' => 'text',
                        'en' => 'For a full price list rather than a sales history, use <b>Reports &rarr; Selling Price Comparison</b>. It puts the regular price and the partner price side by side with the difference, and can be filtered to rows that have a partnership price.',
                        'tl' => 'Para sa buong listahan ng presyo imbes na kasaysayan ng benta, gamitin ang <b>Reports &rarr; Selling Price Comparison</b>. Magkatabi rito ang regular price at partner price kasama ang pagkakaiba, at pwedeng i-filter sa mga row na may partnership price.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'reports-price-comparison.png',
                        'en' => 'Selling Price Comparison, showing a DSWD price against the regular price with the difference.',
                        'tl' => 'Selling Price Comparison, ipinapakita ang presyo ng DSWD laban sa regular price kasama ang pagkakaiba.',
                    ],
                    [
                        'type' => 'note',
                        'en' => 'Admins get the same figures across every branch at once under <b>Reports &rarr; Partnerships</b>, including a trend chart with one line per partner.',
                        'tl' => 'Nakikita ng mga admin ang parehong datos sa lahat ng branch nang sabay sa <b>Reports &rarr; Partnerships</b>, kasama ang trend chart na may isang linya kada partner.',
                    ],
                ],
            ],
            [
                'id' => 'troubleshoot',
                'en' => 'When the partner price does not apply',
                'tl' => 'Kapag hindi gumana ang partner price',
                'blocks' => [
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => 'What you see', 'tl' => 'Ano ang nakikita mo'],
                            ['en' => 'What to check', 'tl' => 'Ano ang tingnan'],
                        ],
                        'rows' => [
                            [
                                ['en' => 'No partner badge on the product row', 'tl' => 'Walang partner badge sa product row'],
                                ['en' => 'The cart is still on Walk-in, or the customer is on a type with no price set for this product.', 'tl' => 'Nasa Walk-in pa ang cart, o ang customer ay nasa type na walang naka-set na presyo para sa produktong ito.'],
                            ],
                            [
                                ['en' => 'Badge shows on the Piece but not the Box', 'tl' => 'May badge sa Piece pero wala sa Box'],
                                ['en' => 'Prices are per packaging. Set the Box price too, in the same Partnership Pricing tab.', 'tl' => 'Kada packaging ang presyo. I-set din ang presyo ng Box, sa parehong Partnership Pricing tab.'],
                            ],
                            [
                                ['en' => 'Worked in one branch, not in another', 'tl' => 'Gumana sa isang branch, hindi sa iba'],
                                ['en' => 'Prices are per branch. Switch to the other branch and set them there.', 'tl' => 'Kada branch ang presyo. Lumipat sa kabilang branch at i-set doon.'],
                            ],
                            [
                                ['en' => 'A percentage came off instead of a fixed price', 'tl' => 'Porsyento ang nabawas, hindi nakatakdang presyo'],
                                ['en' => 'That customer type has a discount percentage set. For LGU and DSWD it should be 0.', 'tl' => 'May naka-set na discount percentage ang customer type na iyon. Para sa LGU at DSWD, dapat 0 ito.'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
