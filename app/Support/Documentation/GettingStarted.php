<?php

declare(strict_types=1);

namespace App\Support\Documentation;

final class GettingStarted
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'sign-in',
                'en' => 'Signing in',
                'tl' => 'Pag-sign in',
                'blocks' => [
                    [
                        'type' => 'steps',
                        'items' => [
                            [
                                'en' => 'Open CitiPOS in your browser and click <b>Sign In</b>.',
                                'tl' => 'Buksan ang CitiPOS sa browser at i-click ang <b>Sign In</b>.',
                            ],
                            [
                                'en' => 'Enter your <b>username</b> and password. This system signs you in by username, not by email address.',
                                'tl' => 'Ilagay ang iyong <b>username</b> at password. Username ang gamit dito, hindi email address.',
                            ],
                            [
                                'en' => 'If you type the wrong password several times in a row, wait a minute before trying again.',
                                'tl' => 'Kapag maraming beses kang nagkamali ng password, maghintay ng isang minuto bago sumubok ulit.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'select-branch',
                'en' => 'Choosing your branch',
                'tl' => 'Pagpili ng branch',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'After signing in you land on <b>Select Branch</b>. Each branch belongs to exactly one module &mdash; Pharmacy, Grocery or Motor Shop. Choose the branch you are working in, then choose whether you are going to the counter (<b>POS</b>) or the back office (<b>Inventory</b>).',
                        'tl' => 'Pagkatapos mag-sign in, mapupunta ka sa <b>Select Branch</b>. Ang bawat branch ay nabibilang sa iisang module lang &mdash; Pharmacy, Grocery o Motor Shop. Piliin ang branch na pinagtatrabahuhan mo, tapos piliin kung papunta ka sa counter (<b>POS</b>) o sa back office (<b>Inventory</b>).',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'select-branch.png',
                        'en' => 'Select Branch: each card shows the branch, its module, and buttons for Inventory or POS.',
                        'tl' => 'Select Branch: ipinapakita ng bawat card ang branch, ang module nito, at ang mga button para sa Inventory o POS.',
                    ],
                    [
                        'type' => 'note',
                        'en' => 'Everything you see afterwards belongs to that one branch &mdash; products, stock, customers, sales and reports. Switching branch switches all of it at once.',
                        'tl' => 'Lahat ng makikita mo pagkatapos ay para lang sa branch na iyon &mdash; products, stock, customers, sales at reports. Kapag nagpalit ka ng branch, sabay-sabay itong nagbabago.',
                    ],
                    [
                        'type' => 'text',
                        'en' => 'To move to another branch later, use the branch dropdown at the top of the sidebar.',
                        'tl' => 'Para lumipat sa ibang branch mamaya, gamitin ang branch dropdown sa itaas ng sidebar.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'branch-switcher.png',
                        'en' => 'The branch switcher at the top of the sidebar.',
                        'tl' => 'Ang branch switcher sa itaas ng sidebar.',
                    ],
                ],
            ],
            [
                'id' => 'around',
                'en' => 'Finding your way around',
                'tl' => 'Paglibot sa sistema',
                'blocks' => [
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => 'Menu', 'tl' => 'Menu'],
                            ['en' => 'What it is for', 'tl' => 'Para saan ito'],
                        ],
                        'rows' => [
                            [['en' => 'POS', 'tl' => 'POS'], ['en' => 'The selling counter', 'tl' => 'Ang counter na pinagbebentahan']],
                            [['en' => 'Dashboard', 'tl' => 'Dashboard'], ['en' => 'Your daily overview and alerts', 'tl' => 'Araw-araw na overview at mga alerto']],
                            [['en' => 'Sales', 'tl' => 'Sales'], ['en' => 'History of every transaction', 'tl' => 'Kasaysayan ng lahat ng transaksyon']],
                            [['en' => 'Products', 'tl' => 'Products'], ['en' => 'The catalogue and prices', 'tl' => 'Ang katalogo at mga presyo']],
                            [['en' => 'Stocks', 'tl' => 'Stocks'], ['en' => 'Physical batches and expiry dates', 'tl' => 'Mga batch at expiration date']],
                            [['en' => 'Purchases', 'tl' => 'Purchases'], ['en' => 'Supplier orders and deliveries', 'tl' => 'Mga order sa supplier at deliveries']],
                            [['en' => 'Expenses', 'tl' => 'Expenses'], ['en' => 'Operating costs', 'tl' => 'Mga gastos sa operasyon']],
                            [['en' => 'Reports', 'tl' => 'Reports'], ['en' => 'Profit, prices and stock movement', 'tl' => 'Kita, presyo at galaw ng stock']],
                            [['en' => 'Customers', 'tl' => 'Customers'], ['en' => 'Customer records and their discount type', 'tl' => 'Talaan ng customer at ang kanilang discount type']],
                            [['en' => 'Settings', 'tl' => 'Settings'], ['en' => 'Suppliers, customer types, units, payment methods, your own profile', 'tl' => 'Suppliers, customer types, units, payment methods, at sarili mong profile']],
                        ],
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            [
                                'en' => 'The <b>moon icon</b> in the top bar switches between dark and light mode.',
                                'tl' => 'Ang <b>moon icon</b> sa itaas ay nagpapalit ng dark at light mode.',
                            ],
                            [
                                'en' => 'The <b>Calc</b> button, or the <kbd>C</kbd> key, opens a calculator at the counter.',
                                'tl' => 'Ang <b>Calc</b> button, o ang <kbd>C</kbd> key, ay nagbubukas ng calculator sa counter.',
                            ],
                            [
                                'en' => 'Your name and role always show at the top right, so you can confirm whose account is open.',
                                'tl' => 'Palaging nakalagay sa kanang itaas ang pangalan at role mo, para makita kung kaninong account ang bukas.',
                            ],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-calculator.png',
                        'en' => 'The built-in calculator, opened over the POS.',
                        'tl' => 'Ang built-in na calculator, bukas sa ibabaw ng POS.',
                    ],
                ],
            ],
            [
                'id' => 'no-access',
                'en' => 'If a page says you are not allowed',
                'tl' => 'Kung may lumabas na bawal ka sa isang page',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Access is controlled in two layers, and both must allow you in:',
                        'tl' => 'May dalawang layer ang access, at dapat parehong pumayag:',
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            [
                                'en' => '<b>Module access</b> &mdash; whether your role can enter Pharmacy, Grocery or Motor Shop at all.',
                                'tl' => '<b>Module access</b> &mdash; kung kaya bang pumasok ng role mo sa Pharmacy, Grocery o Motor Shop.',
                            ],
                            [
                                'en' => '<b>Permissions</b> &mdash; whether you personally may open a specific screen, such as Purchases or Reports.',
                                'tl' => '<b>Permissions</b> &mdash; kung ikaw mismo ay pwedeng magbukas ng partikular na screen, tulad ng Purchases o Reports.',
                            ],
                        ],
                    ],
                    [
                        'type' => 'note',
                        'en' => 'Permissions are given per person, so two pharmacists can have different access. Ask your admin to grant what you need under <b>Users</b>.',
                        'tl' => 'Per person ang pagbibigay ng permissions, kaya pwedeng magkaiba ang access ng dalawang pharmacist. Hilingin sa admin mo na bigyan ka ng kailangan mo sa <b>Users</b>.',
                    ],
                ],
            ],
        ];
    }
}
