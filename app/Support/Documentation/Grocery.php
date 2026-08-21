<?php

declare(strict_types=1);

namespace App\Support\Documentation;

final class Grocery
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'same',
                'en' => 'How grocery differs',
                'tl' => 'Ano ang pagkakaiba ng grocery',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'The grocery counter and inventory work the same way as the pharmacy, so the <b>Pharmacist</b> section applies to you almost entirely. These are the real differences.',
                        'tl' => 'Pareho ang gamit ng grocery counter at inventory sa pharmacy, kaya halos lahat ng nasa <b>Pharmacist</b> na bahagi ay para rin sa iyo. Ito ang totoong mga pagkakaiba.',
                    ],
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => '', 'tl' => ''],
                            ['en' => 'Pharmacy', 'tl' => 'Pharmacy'],
                            ['en' => 'Grocery', 'tl' => 'Grocery'],
                        ],
                        'rows' => [
                            [
                                ['en' => 'Extra product fields', 'tl' => 'Dagdag na product fields'],
                                ['en' => 'Generic name, dosage, form, prescription flag', 'tl' => 'Generic name, dosage, form, prescription flag'],
                                ['en' => 'None &mdash; just brand name and description', 'tl' => 'Wala &mdash; brand name at description lang'],
                            ],
                            [
                                ['en' => 'Decimal quantities', 'tl' => 'Desimal na dami'],
                                ['en' => 'Rarely used', 'tl' => 'Bihirang gamitin'],
                                ['en' => 'Used for goods sold by weight', 'tl' => 'Ginagamit para sa tinitimbang na paninda'],
                            ],
                            [
                                ['en' => 'Partnership pricing', 'tl' => 'Partnership pricing'],
                                ['en' => 'Common', 'tl' => 'Karaniwan'],
                                ['en' => 'Possible, but rarely used', 'tl' => 'Posible, pero bihirang gamitin'],
                            ],
                            [
                                ['en' => 'Bulk Price Book', 'tl' => 'Bulk Price Book'],
                                ['en' => 'Available', 'tl' => 'Available'],
                                ['en' => 'Not available', 'tl' => 'Hindi available'],
                            ],
                            [
                                ['en' => 'Selling Price Comparison report', 'tl' => 'Selling Price Comparison report'],
                                ['en' => 'Available', 'tl' => 'Available'],
                                ['en' => 'Not available', 'tl' => 'Hindi available'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'pos',
                'en' => 'The grocery counter',
                'tl' => 'Ang grocery counter',
                'blocks' => [
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'Choose the customer first &mdash; <b>Walk-in</b>, or switch to <b>Customer</b> and pick one.', 'tl' => 'Piliin muna ang customer &mdash; <b>Walk-in</b>, o lumipat sa <b>Customer</b> at pumili.'],
                            ['en' => 'Find the item by search, by category, or by scanning its barcode.', 'tl' => 'Hanapin ang item sa search, sa category, o sa pag-scan ng barcode.'],
                            ['en' => 'Pick the packaging &mdash; sack, case or piece &mdash; if there is more than one.', 'tl' => 'Piliin ang packaging &mdash; sako, kaha o piraso &mdash; kung mahigit isa ito.'],
                            ['en' => 'Click the row to add it, then adjust the quantity in the cart.', 'tl' => 'I-click ang row para maidagdag, tapos ayusin ang dami sa cart.'],
                            ['en' => 'Press <b>Checkout</b> or <kbd>F4</kbd>, choose the payment method, enter the amount received and confirm.', 'tl' => 'Pindutin ang <b>Checkout</b> o <kbd>F4</kbd>, piliin ang payment method, ilagay ang natanggap na halaga at kumpirmahin.'],
                        ],
                    ],
                    [
                        'type' => 'note',
                        'en' => 'For goods sold by weight, the unit must be set to allow decimals. If you cannot type <b>1.5</b>, that unit is whole-number only &mdash; change it in Settings, or sell in a smaller unit.',
                        'tl' => 'Para sa mga tinitimbang na paninda, dapat naka-set ang unit na tumanggap ng desimal. Kung hindi mo mai-type ang <b>1.5</b>, buong numero lang ang tanggap ng unit na iyon &mdash; palitan ito sa Settings, o magbenta sa mas maliit na unit.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-cart-walkin.png',
                        'en' => 'A walk-in cart with one line, showing unit price, quantity controls and the total.',
                        'tl' => 'Isang walk-in na cart na may isang linya, ipinapakita ang unit price, kontrol sa dami at ang total.',
                    ],
                    [
                        'type' => 'text',
                        'en' => '<b>Price Check</b> (<kbd>F2</kbd>) works here too. It only reads, so you can answer a price question with a half-built cart and nothing is disturbed.',
                        'tl' => 'Gumagana rin dito ang <b>Price Check</b> (<kbd>F2</kbd>). Nagbabasa lang ito, kaya kaya mong sumagot ng tanong sa presyo kahit may kalahating cart ka na at walang magugulo.',
                    ],
                ],
            ],
            [
                'id' => 'inventory',
                'en' => 'Grocery inventory',
                'tl' => 'Grocery inventory',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Products, Stocks, Purchases, Sales, Customers, Expenses and Reports all behave as described in the <b>Pharmacist</b> section. Use these notes for the grocery-specific parts.',
                        'tl' => 'Ang Products, Stocks, Purchases, Sales, Customers, Expenses at Reports ay gumagana gaya ng nakasaad sa <b>Pharmacist</b> na bahagi. Gamitin ang mga paalalang ito para sa mga partikular sa grocery.',
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => 'When creating a product you enter brand name, supplier, category and description &mdash; there are no dosage or prescription fields.', 'tl' => 'Sa paggawa ng produkto, ilalagay mo ang brand name, supplier, category at description &mdash; walang dosage o prescription na field.'],
                            ['en' => 'A product image is optional. Square works best, ideal 800x800px, up to 2MB. Products without one show a box icon everywhere.', 'tl' => 'Opsyonal ang larawan ng produkto. Mas maganda ang square, ideal 800x800px, hanggang 2MB. Ang walang larawan ay may box icon sa lahat ng lugar.'],
                            ['en' => 'Expiry still applies to grocery batches, so keep using the <b>Stocks</b> page and its Expiring Soon and Expired tabs.', 'tl' => 'May expiry pa rin ang mga grocery batch, kaya patuloy na gamitin ang <b>Stocks</b> page at ang Expiring Soon at Expired na tab nito.'],
                            ['en' => 'Barcode scanning to the cart is supported for grocery when the branch has it switched on.', 'tl' => 'Suportado ang barcode scanning papunta sa cart para sa grocery kapag naka-on ito sa branch.'],
                        ],
                    ],
                    [
                        'type' => 'warn',
                        'en' => 'Stock added by adjustment is recorded at zero cost. For anything you actually bought, record a purchase instead so the cost is captured and your margin stays honest.',
                        'tl' => 'Ang stock na idinagdag sa pamamagitan ng adjustment ay walang naitalang cost. Para sa mga talagang binili mo, mag-record ng purchase para makuha ang halaga at manatiling tama ang margin mo.',
                    ],
                ],
            ],
        ];
    }
}
