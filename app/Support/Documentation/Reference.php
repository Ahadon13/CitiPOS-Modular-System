<?php

declare(strict_types=1);

namespace App\Support\Documentation;

/**
 * Look-up material rather than walkthroughs: shortcuts, roles, and the
 * vocabulary the rest of the manual uses.
 *
 * The shortcut table is transcribed from the live key handlers in
 * resources/js/app.js and the F2 bindings on the POS and Products pages, not
 * from an earlier draft of the manual -- F1 focuses the search box, it does
 * not open the scanner.
 */
final class Reference
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'shortcuts',
                'en' => 'Keyboard shortcuts',
                'tl' => 'Mga keyboard shortcut',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'These work on the POS screen. Learn the three you use most and the counter gets noticeably faster.',
                        'tl' => 'Gumagana ang mga ito sa POS screen. Kabisaduhin ang tatlong pinakaginagamit mo at mapapabilis nang husto ang counter.',
                    ],
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => 'Key', 'tl' => 'Key'],
                            ['en' => 'What it does', 'tl' => 'Ano ang ginagawa'],
                            ['en' => 'Where', 'tl' => 'Saan'],
                        ],
                        'rows' => [
                            [
                                ['en' => '<b>Ctrl&nbsp;+&nbsp;K</b>', 'tl' => '<b>Ctrl&nbsp;+&nbsp;K</b>'],
                                ['en' => 'Jump to the product search box.', 'tl' => 'Tumalon sa product search box.'],
                                ['en' => 'POS', 'tl' => 'POS'],
                            ],
                            [
                                ['en' => '<b>F1</b>', 'tl' => '<b>F1</b>'],
                                ['en' => 'Also jumps to the product search box.', 'tl' => 'Tumatalon din sa product search box.'],
                                ['en' => 'POS', 'tl' => 'POS'],
                            ],
                            [
                                ['en' => '<b>F2</b>', 'tl' => '<b>F2</b>'],
                                ['en' => 'Open <b>Price Check</b> / <b>Scan &amp; Look up</b>. Read-only &mdash; it never touches the cart.', 'tl' => 'Buksan ang <b>Price Check</b> / <b>Scan &amp; Look up</b>. Pagbabasa lang &mdash; hindi nito ginagalaw ang cart.'],
                                ['en' => 'POS and Products', 'tl' => 'POS at Products'],
                            ],
                            [
                                ['en' => '<b>F4</b>', 'tl' => '<b>F4</b>'],
                                ['en' => 'Open Checkout for the current order.', 'tl' => 'Buksan ang Checkout para sa kasalukuyang order.'],
                                ['en' => 'POS', 'tl' => 'POS'],
                            ],
                            [
                                ['en' => '<b>C</b>', 'tl' => '<b>C</b>'],
                                ['en' => 'Show or hide the on-screen calculator.', 'tl' => 'Ipakita o itago ang calculator sa screen.'],
                                ['en' => 'Pharmacy and Grocery POS', 'tl' => 'Pharmacy at Grocery POS'],
                            ],
                            [
                                ['en' => '<b>Esc</b>', 'tl' => '<b>Esc</b>'],
                                ['en' => 'Clears the whole cart.', 'tl' => 'Bina-blangko ang buong cart.'],
                                ['en' => 'POS', 'tl' => 'POS'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warn',
                        'en' => '<b>Esc</b> empties the cart, and <b>C</b> is a bare letter key. If a shortcut fires while you meant to type, click into the search box first, and rebuild the order rather than guessing at what was in it.',
                        'tl' => 'Bina-blangko ng <b>Esc</b> ang cart, at isang letra lang ang <b>C</b>. Kung pumutok ang shortcut habang nagta-type ka, i-click muna ang search box, at ulitin ang pag-encode ng order kaysa manghula kung ano ang laman nito.',
                    ],
                ],
            ],
            [
                'id' => 'roles',
                'en' => 'Roles at a glance',
                'tl' => 'Mga role sa isang tingin',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'CitiPOS ships with eight roles. A role is the starting point &mdash; individual permissions can still be added to one person without changing everyone who shares that role.',
                        'tl' => 'May walong role ang CitiPOS. Panimulang punto lang ang role &mdash; pwede pa ring dagdagan ng permissions ang isang tao nang hindi naaapektuhan ang iba na kapareho ng role.',
                    ],
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => 'Role', 'tl' => 'Role'],
                            ['en' => 'What it is for', 'tl' => 'Para saan ito'],
                        ],
                        'rows' => [
                            [
                                ['en' => '<b>Super Admin</b>', 'tl' => '<b>Super Admin</b>'],
                                ['en' => 'Everything, in every branch and module. Ignores the Module Access grid entirely.', 'tl' => 'Lahat, sa bawat branch at module. Hindi ito sakop ng Module Access grid.'],
                            ],
                            [
                                ['en' => '<b>Admin</b>', 'tl' => '<b>Admin</b>'],
                                ['en' => 'Branches, staff accounts, settings and the unified reports. Modules follow the Module Access grid.', 'tl' => 'Mga branch, account ng staff, settings at ang pinagsamang reports. Sumusunod sa Module Access grid ang modules.'],
                            ],
                            [
                                ['en' => '<b>Pharmacist</b>', 'tl' => '<b>Pharmacist</b>'],
                                ['en' => 'The Pharmacy module: counter, inventory, purchases and reports for their branch.', 'tl' => 'Ang Pharmacy module: counter, imbentaryo, purchases at reports para sa kanilang branch.'],
                            ],
                            [
                                ['en' => '<b>Grocery Cashier</b>', 'tl' => '<b>Grocery Cashier</b>'],
                                ['en' => 'The Grocery module, same shape as the Pharmacist role.', 'tl' => 'Ang Grocery module, kaparehong hugis ng Pharmacist na role.'],
                            ],
                            [
                                ['en' => '<b>Motor Shop Cashier</b>', 'tl' => '<b>Motor Shop Cashier</b>'],
                                ['en' => 'The Motor Shop counter &mdash; parts and labour on one sale.', 'tl' => 'Ang Motor Shop counter &mdash; parts at labor sa iisang benta.'],
                            ],
                            [
                                ['en' => '<b>Chief Mechanic</b>', 'tl' => '<b>Chief Mechanic</b>'],
                                ['en' => 'Motor Shop with oversight of the work: normally also sees reports.', 'tl' => 'Motor Shop na may pangangasiwa sa trabaho: karaniwang nakakakita rin ng reports.'],
                            ],
                            [
                                ['en' => '<b>Mechanic</b>', 'tl' => '<b>Mechanic</b>'],
                                ['en' => 'Motor Shop. Jobs are assigned to this person on the Add Service form.', 'tl' => 'Motor Shop. Dito ina-assign ang trabaho sa Add Service form.'],
                            ],
                            [
                                ['en' => '<b>Distributor</b>', 'tl' => '<b>Distributor</b>'],
                                ['en' => 'A supply-side role, attached to branches the same way as any other account.', 'tl' => 'Role sa panig ng suplay, naka-attach sa branch gaya ng ibang account.'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'note',
                        'en' => 'Two separate switches decide what someone sees: <b>Module Access</b> is per role, and <b>Assigned Branches</b> is per user. Someone can hold the right role and still see nothing, because no branch was assigned to them.',
                        'tl' => 'Dalawang magkahiwalay na switch ang nagtatakda kung ano ang makikita ng isang tao: ang <b>Module Access</b> ay per role, at ang <b>Assigned Branches</b> ay per user. Pwedeng tama ang role ng isang tao pero wala pa rin siyang makita, dahil walang branch na naka-assign sa kanya.',
                    ],
                ],
            ],
            [
                'id' => 'glossary',
                'en' => 'Words this manual uses',
                'tl' => 'Mga salitang ginagamit ng gabay na ito',
                'blocks' => [
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => 'Term', 'tl' => 'Salita'],
                            ['en' => 'Meaning', 'tl' => 'Kahulugan'],
                        ],
                        'rows' => [
                            [
                                ['en' => '<b>Base unit</b>', 'tl' => '<b>Base unit</b>'],
                                ['en' => 'The smallest unit you sell &mdash; a Piece or Tablet. Stock is counted in this unit, and every larger pack converts down to it.', 'tl' => 'Ang pinakamaliit na unit na binebenta mo &mdash; Piece o Tablet. Dito binibilang ang stock, at dito kino-convert ang lahat ng mas malaking pack.'],
                            ],
                            [
                                ['en' => '<b>Packaging</b>', 'tl' => '<b>Packaging</b>'],
                                ['en' => 'A way of selling the same product &mdash; Piece, Box of 10, Case of 100. Each one carries its own price.', 'tl' => 'Paraan ng pagbenta ng parehong produkto &mdash; Piece, Box na 10, Case na 100. May sariling presyo ang bawat isa.'],
                            ],
                            [
                                ['en' => '<b>Batch</b>', 'tl' => '<b>Batch</b>'],
                                ['en' => 'One delivery of a product, with its own cost and expiry date. Selling deducts from the batch expiring soonest.', 'tl' => 'Isang deliver ng produkto, may sariling halaga at expiry. Ang benta ay binabawas sa batch na pinakamalapit nang mag-expire.'],
                            ],
                            [
                                ['en' => '<b>Customer type</b>', 'tl' => '<b>Customer type</b>'],
                                ['en' => 'A label on a customer &mdash; LGU, DSWD, PWD, Senior Citizen, Regular. This is what triggers partnership pricing.', 'tl' => 'Label sa customer &mdash; LGU, DSWD, PWD, Senior Citizen, Regular. Ito ang nagpapagana ng partnership pricing.'],
                            ],
                            [
                                ['en' => '<b>Partnership price</b>', 'tl' => '<b>Partnership price</b>'],
                                ['en' => 'A mandated price for one customer type, on one packaging, at one branch. Blank means the regular retail price applies.', 'tl' => 'Itinakdang presyo para sa isang customer type, sa isang packaging, sa isang branch. Kapag blangko, ang regular na retail price ang gagamitin.'],
                            ],
                            [
                                ['en' => '<b>Reorder level</b>', 'tl' => '<b>Reorder level</b>'],
                                ['en' => 'The quantity at which a product starts showing in Low Stock.', 'tl' => 'Ang dami kung saan magsisimulang lumabas ang produkto sa Low Stock.'],
                            ],
                            [
                                ['en' => '<b>Gross profit</b>', 'tl' => '<b>Gross profit</b>'],
                                ['en' => 'Revenue minus what the goods cost you.', 'tl' => 'Kita bawas ang halaga ng paninda sa iyo.'],
                            ],
                            [
                                ['en' => '<b>Net profit</b>', 'tl' => '<b>Net profit</b>'],
                                ['en' => 'Gross profit minus the operating costs you recorded under Expenses.', 'tl' => 'Gross profit bawas ang mga gastos sa operasyon na naitala mo sa Expenses.'],
                            ],
                            [
                                ['en' => '<b>Savings given</b>', 'tl' => '<b>Savings given</b>'],
                                ['en' => 'What the partnership cost you: the regular price minus the partner price, across every partnered sale.', 'tl' => 'Ang naging gastos sa iyo ng partnership: regular na presyo bawas ang presyong pang-partner, sa lahat ng partnered na benta.'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'help',
                'en' => 'When something looks wrong',
                'tl' => 'Kapag may mukhang mali',
                'blocks' => [
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => 'What you see', 'tl' => 'Ang nakikita mo'],
                            ['en' => 'Usual cause', 'tl' => 'Karaniwang dahilan'],
                        ],
                        'rows' => [
                            [
                                ['en' => 'A page or menu item is missing.', 'tl' => 'May page o menu item na wala.'],
                                ['en' => 'Your role or your permissions do not include it. Ask an admin.', 'tl' => 'Hindi kasama ito sa role o permissions mo. Magtanong sa admin.'],
                            ],
                            [
                                ['en' => 'The product is not in the POS search.', 'tl' => 'Wala sa POS search ang produkto.'],
                                ['en' => 'It has no stock at this branch, or it belongs to another module.', 'tl' => 'Walang stock sa branch na ito, o sa ibang module ito nabibilang.'],
                            ],
                            [
                                ['en' => 'The partner price did not apply.', 'tl' => 'Hindi na-apply ang partner price.'],
                                ['en' => 'The cart is on Walk-in, the customer has no type, or no price was set for that packaging at this branch.', 'tl' => 'Nasa Walk-in ang cart, walang type ang customer, o walang naka-set na presyo para sa packaging na iyon sa branch na ito.'],
                            ],
                            [
                                ['en' => 'The scanner types into the search box instead of adding the item.', 'tl' => 'Nagta-type sa search box ang scanner imbes na idagdag ang item.'],
                                ['en' => 'Barcode scanning is switched off for this branch, or the product has no barcode saved.', 'tl' => 'Naka-off ang barcode scanning sa branch na ito, o walang naka-save na barcode ang produkto.'],
                            ],
                            [
                                ['en' => 'Net profit looks too high.', 'tl' => 'Mukhang sobrang taas ng net profit.'],
                                ['en' => 'Operating costs were never recorded under Expenses.', 'tl' => 'Hindi naitala sa Expenses ang mga gastos sa operasyon.'],
                            ],
                            [
                                ['en' => 'Stock is right on the shelf but wrong on screen.', 'tl' => 'Tama ang stock sa istante pero mali sa screen.'],
                                ['en' => 'A delivery was never received in, or an adjustment was recorded against the wrong batch.', 'tl' => 'May deliver na hindi na-receive, o may adjustment na naitala sa maling batch.'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'note',
                        'en' => 'If a screen stops matching this manual, the manual is what is out of date. Report it so it can be corrected.',
                        'tl' => 'Kung may screen na hindi na tugma sa gabay na ito, ang gabay ang luma na. Ipaalam ito para maitama.',
                    ],
                ],
            ],
        ];
    }
}
