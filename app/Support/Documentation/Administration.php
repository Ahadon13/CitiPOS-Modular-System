<?php

declare(strict_types=1);

namespace App\Support\Documentation;

final class Administration
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'overview',
                'en' => 'What an admin can do',
                'tl' => 'Ano ang kayang gawin ng admin',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Admins and owners work above the branches. Where a pharmacist sees one branch, you see all of them at once: combined figures, a branch-by-branch comparison, and the setup that decides what everyone else is allowed to do.',
                        'tl' => 'Ang admin at may-ari ay nasa itaas ng mga branch. Kung ang pharmacist ay isang branch lang ang nakikita, ikaw ay lahat ng branch nang sabay: pinagsamang datos, paghahambing kada branch, at ang setup na nagtatakda kung ano ang pwedeng gawin ng lahat.',
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => '<b>Owner Hub</b> &mdash; pick which branch or area to jump into.', 'tl' => '<b>Owner Hub</b> &mdash; piliin kung aling branch o bahagi ang pupuntahan.'],
                            ['en' => '<b>Dashboard</b> &mdash; combined performance across every branch.', 'tl' => '<b>Dashboard</b> &mdash; pinagsamang performance ng lahat ng branch.'],
                            ['en' => '<b>Branches</b> &mdash; create branches and set their options.', 'tl' => '<b>Branches</b> &mdash; gumawa ng branch at i-set ang mga opsyon nito.'],
                            ['en' => '<b>Users</b> &mdash; staff accounts, roles and permissions.', 'tl' => '<b>Users</b> &mdash; account ng staff, roles at permissions.'],
                            ['en' => '<b>Module Access</b> &mdash; which roles may enter which modules.', 'tl' => '<b>Module Access</b> &mdash; kung aling role ang pwedeng pumasok sa aling module.'],
                            ['en' => '<b>Reports</b> &mdash; the full business picture, in tabs.', 'tl' => '<b>Reports</b> &mdash; ang buong larawan ng negosyo, naka-tab.'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'branches',
                'en' => 'Branches',
                'tl' => 'Mga Branch',
                'blocks' => [
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'Go to <b>Branches</b> and click <b>Add Branch</b>.', 'tl' => 'Pumunta sa <b>Branches</b> at i-click ang <b>Add Branch</b>.'],
                            ['en' => 'Enter the name and address.', 'tl' => 'Ilagay ang pangalan at address.'],
                            ['en' => 'Choose the <b>module</b>: Pharmacy, Grocery or Motor Shop. A branch belongs to exactly one, and this should not be changed casually afterwards.', 'tl' => 'Piliin ang <b>module</b>: Pharmacy, Grocery o Motor Shop. Iisa lang ang module ng bawat branch, at hindi ito dapat basta-basta baguhin pagkatapos.'],
                            ['en' => 'Tick <b>Branch is currently active</b> so staff can select it.', 'tl' => 'I-tsek ang <b>Branch is currently active</b> para mapili ito ng staff.'],
                            ['en' => 'Optionally switch on <b>Barcode Scanner</b> for this branch.', 'tl' => 'Opsyonal na i-on ang <b>Barcode Scanner</b> para sa branch na ito.'],
                        ],
                    ],
                    [
                        'type' => 'note',
                        'en' => 'Barcode scanning is off everywhere by default, and the counter behaves exactly as it always has until you switch it on. When enabled you can also tune the minimum barcode length and the keystroke threshold &mdash; raise the threshold if scans are being missed or split in half.',
                        'tl' => 'Naka-off ang barcode scanning sa lahat ng branch bilang default, at gagana ang counter gaya ng dati hanggang i-on mo ito. Kapag naka-on, pwede mo ring ayusin ang minimum barcode length at ang keystroke threshold &mdash; taasan ang threshold kung may hindi nababasang scan o nahahati ito.',
                    ],
                    [
                        'type' => 'text',
                        'en' => 'Clicking a branch opens its full picture: revenue, profit, stock value, products, sales, purchase orders, partnership sales and the stock movement ledger. Each of those tables exports to Excel, and the partnership report also exports to PDF.',
                        'tl' => 'Kapag na-click ang isang branch, bubukas ang buong larawan nito: kita, tubo, halaga ng stock, produkto, benta, purchase orders, partnership sales at ang stock movement ledger. Bawat talahanayan ay pwedeng i-export sa Excel, at ang partnership report ay pwede ring PDF.',
                    ],
                ],
            ],
            [
                'id' => 'users',
                'en' => 'Users and permissions',
                'tl' => 'Users at permissions',
                'blocks' => [
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'Go to <b>Users</b> and add or edit a person.', 'tl' => 'Pumunta sa <b>Users</b> at magdagdag o mag-edit ng tao.'],
                            ['en' => 'Give them a <b>Base Role</b>. This decides which modules they may enter.', 'tl' => 'Bigyan sila ng <b>Base Role</b>. Ito ang nagtatakda kung aling module ang mapapasukan nila.'],
                            ['en' => 'Assign the <b>branches</b> they can access, and the branch they start in.', 'tl' => 'I-assign ang mga <b>branch</b> na pwede nilang gamitin, at ang branch na sisimulan nila.'],
                            ['en' => 'Tick the individual <b>Direct User Permissions</b> they need.', 'tl' => 'I-tsek ang mga indibidwal na <b>Direct User Permissions</b> na kailangan nila.'],
                            ['en' => 'Leave the password blank when editing to keep their current one.', 'tl' => 'Iwanang blangko ang password kapag nag-e-edit para manatili ang kasalukuyan nila.'],
                        ],
                    ],
                    [
                        'type' => 'warn',
                        'en' => 'A cashier still needs the <b>Access Point of Sale</b> permission, even if their role is Cashier. It is the first thing to check when a new hire says they cannot ring up a sale.',
                        'tl' => 'Kailangan pa rin ng cashier ang <b>Access Point of Sale</b> permission, kahit Cashier ang role nila. Ito ang unang tingnan kapag may bagong empleyado na hindi makapag-benta.',
                    ],
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => 'Permission', 'tl' => 'Permission'],
                            ['en' => 'Unlocks', 'tl' => 'Ano ang bubuksan nito'],
                        ],
                        'rows' => [
                            [['en' => 'Access Point of Sale', 'tl' => 'Access Point of Sale'], ['en' => 'The POS screen and taking payment', 'tl' => 'Ang POS screen at pagtanggap ng bayad']],
                            [['en' => 'Manage Sales', 'tl' => 'Manage Sales'], ['en' => 'Viewing the transaction history', 'tl' => 'Pagtingin sa kasaysayan ng transaksyon']],
                            [['en' => 'Manage Products', 'tl' => 'Manage Products'], ['en' => 'Creating, editing and importing products', 'tl' => 'Paggawa, pag-edit at pag-import ng produkto']],
                            [['en' => 'Manage Stocks', 'tl' => 'Manage Stocks'], ['en' => 'Stock levels, batches and adjustments', 'tl' => 'Dami ng stock, batches at adjustments']],
                            [['en' => 'Manage Purchase Orders', 'tl' => 'Manage Purchase Orders'], ['en' => 'Raising and receiving purchases', 'tl' => 'Paggawa at pagtanggap ng purchases']],
                            [['en' => 'Manage Expenses', 'tl' => 'Manage Expenses'], ['en' => 'Recording operating costs', 'tl' => 'Pagtatala ng gastos sa operasyon']],
                            [['en' => 'Manage Customers', 'tl' => 'Manage Customers'], ['en' => 'Customer records and customer types', 'tl' => 'Talaan ng customer at customer types']],
                            [['en' => 'Access Store Dashboard', 'tl' => 'Access Store Dashboard'], ['en' => 'The branch dashboard', 'tl' => 'Ang dashboard ng branch']],
                            [['en' => 'View Store Reports', 'tl' => 'View Store Reports'], ['en' => 'Branch-level reports', 'tl' => 'Mga report sa antas ng branch']],
                            [['en' => 'Access Admin Dashboard', 'tl' => 'Access Admin Dashboard'], ['en' => 'The owner dashboard', 'tl' => 'Ang dashboard ng may-ari']],
                            [['en' => 'Manage Branches', 'tl' => 'Manage Branches'], ['en' => 'Creating and editing branches', 'tl' => 'Paggawa at pag-edit ng branch']],
                            [['en' => 'Manage Users', 'tl' => 'Manage Users'], ['en' => 'Staff accounts and their permissions', 'tl' => 'Account ng staff at ang permissions nila']],
                            [['en' => 'Manage Module Access', 'tl' => 'Manage Module Access'], ['en' => 'Which roles may enter which modules', 'tl' => 'Kung aling role ang pwede sa aling module']],
                            [['en' => 'View Admin Reports', 'tl' => 'View Admin Reports'], ['en' => 'The full business reports', 'tl' => 'Ang buong business reports']],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'module-access',
                'en' => 'Module access by role',
                'tl' => 'Module access ayon sa role',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => '<b>Module Access</b> controls which roles may enter which modules. This is separate from permissions: module access decides whether you can enter Pharmacy at all, while permissions decide which screens you see once inside.',
                        'tl' => 'Kinokontrol ng <b>Module Access</b> kung aling role ang pwedeng pumasok sa aling module. Hiwalay ito sa permissions: ang module access ang nagpapasya kung makakapasok ka ba sa Pharmacy, samantalang ang permissions ang nagpapasya kung anong screen ang makikita mo sa loob.',
                    ],
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => 'Role', 'tl' => 'Role'],
                            ['en' => 'Pharmacy', 'tl' => 'Pharmacy'],
                            ['en' => 'Grocery', 'tl' => 'Grocery'],
                            ['en' => 'Motor Shop', 'tl' => 'Motor Shop'],
                        ],
                        'rows' => [
                            [['en' => 'Super Admin', 'tl' => 'Super Admin'], ['en' => 'Yes', 'tl' => 'Oo'], ['en' => 'Yes', 'tl' => 'Oo'], ['en' => 'Yes', 'tl' => 'Oo']],
                            [['en' => 'Pharmacist', 'tl' => 'Pharmacist'], ['en' => 'Yes', 'tl' => 'Oo'], ['en' => 'No', 'tl' => 'Hindi'], ['en' => 'No', 'tl' => 'Hindi']],
                            [['en' => 'Grocery Cashier', 'tl' => 'Grocery Cashier'], ['en' => 'No', 'tl' => 'Hindi'], ['en' => 'Yes', 'tl' => 'Oo'], ['en' => 'No', 'tl' => 'Hindi']],
                            [['en' => 'Motor Shop Cashier', 'tl' => 'Motor Shop Cashier'], ['en' => 'No', 'tl' => 'Hindi'], ['en' => 'No', 'tl' => 'Hindi'], ['en' => 'Yes', 'tl' => 'Oo']],
                            [['en' => 'Chief Mechanic', 'tl' => 'Chief Mechanic'], ['en' => 'No', 'tl' => 'Hindi'], ['en' => 'No', 'tl' => 'Hindi'], ['en' => 'Yes', 'tl' => 'Oo']],
                            [['en' => 'Mechanic', 'tl' => 'Mechanic'], ['en' => 'No', 'tl' => 'Hindi'], ['en' => 'No', 'tl' => 'Hindi'], ['en' => 'Yes', 'tl' => 'Oo']],
                            [['en' => 'Admin', 'tl' => 'Admin'], ['en' => 'None by default', 'tl' => 'Wala bilang default'], ['en' => 'None by default', 'tl' => 'Wala bilang default'], ['en' => 'None by default', 'tl' => 'Wala bilang default']],
                        ],
                    ],
                    [
                        'type' => 'note',
                        'en' => 'Admins have no module access out of the box, because they normally work from the admin side. Grant it explicitly if an admin also needs to work a counter.',
                        'tl' => 'Walang module access ang admin sa simula, dahil karaniwan silang nagtatrabaho sa admin side. Bigyan ito nang tahasan kung kailangan ding magtrabaho ng admin sa counter.',
                    ],
                ],
            ],
            [
                'id' => 'reports',
                'en' => 'Business reports',
                'tl' => 'Business reports',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'The admin <b>Reports</b> page is organised into tabs. Branch, module and date range sit at the top and apply to all of them.',
                        'tl' => 'Ang admin <b>Reports</b> page ay nakahati sa mga tab. Nasa itaas ang branch, module at date range, at nalalapat ito sa lahat.',
                    ],
                    [
                        'type' => 'table',
                        'head' => [
                            ['en' => 'Tab', 'tl' => 'Tab'],
                            ['en' => 'Answers', 'tl' => 'Ano ang sinasagot'],
                        ],
                        'rows' => [
                            [['en' => 'Overview', 'tl' => 'Overview'], ['en' => 'Revenue, expenses, gross and net profit, and the trend over time', 'tl' => 'Kita, gastos, gross at net profit, at ang trend sa paglipas ng panahon']],
                            [['en' => 'Partnerships', 'tl' => 'Partnerships'], ['en' => 'What each partner bought, what they paid, and the subsidy you carried', 'tl' => 'Ano ang binili ng bawat partner, magkano ang binayad, at ang subsidy na sinagot mo']],
                            [['en' => 'Branches & Payments', 'tl' => 'Branches & Payments'], ['en' => 'Branch comparison, payment reconciliation and sale statuses', 'tl' => 'Paghahambing ng branch, reconciliation ng bayad at status ng benta']],
                            [['en' => 'Cashiers', 'tl' => 'Cashiers'], ['en' => 'Sales per person, including the discounts they applied', 'tl' => 'Benta kada tao, kasama ang mga discount na ibinigay nila']],
                            [['en' => 'Products', 'tl' => 'Products'], ['en' => 'Best sellers and the margin each one earned', 'tl' => 'Pinakamabenta at ang margin na kinita ng bawat isa']],
                            [['en' => 'Stock Ledger', 'tl' => 'Stock Ledger'], ['en' => 'Every stock movement in and out', 'tl' => 'Bawat pasok at labas ng stock']],
                        ],
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => 'Partnership, Branch and Cashier reports export to both <b>Excel</b> and <b>PDF</b>. PDFs print the active filters in the header, so a saved file still says what it covers.', 'tl' => 'Ang Partnership, Branch at Cashier reports ay pwedeng i-export sa <b>Excel</b> at <b>PDF</b>. Nakalimbag sa header ng PDF ang mga aktibong filter, kaya alam pa rin kung ano ang saklaw ng naka-save na file.'],
                            ['en' => 'PDFs are capped at 2,000 detail rows. For a complete data dump, use Excel &mdash; it has no limit.', 'tl' => 'May hangganan ang PDF na 2,000 detalyadong row. Para sa kumpletong datos, gamitin ang Excel &mdash; walang limitasyon ito.'],
                            ['en' => 'Wide date ranges automatically switch the charts from daily to weekly, monthly or quarterly points, so a multi-year range stays readable.', 'tl' => 'Kapag malawak ang saklaw ng petsa, awtomatikong lumilipat ang chart mula daily patungong weekly, monthly o quarterly, para manatiling mabasa ang maraming taon na saklaw.'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'settings',
                'en' => 'System configuration',
                'tl' => 'System configuration',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'The <b>Settings</b> page holds the master data every other screen draws from.',
                        'tl' => 'Nasa <b>Settings</b> page ang master data na ginagamit ng lahat ng ibang screen.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'settings.png',
                        'en' => 'Settings: Suppliers, Customer Types, Measurement Units and Payment Methods, plus your own account profile and password.',
                        'tl' => 'Settings: Suppliers, Customer Types, Measurement Units at Payment Methods, pati ang sarili mong account profile at password.',
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => '<b>Suppliers</b> &mdash; who you buy from. Needed before raising a purchase order.', 'tl' => '<b>Suppliers</b> &mdash; kung kanino ka bumibili. Kailangan bago makagawa ng purchase order.'],
                            ['en' => '<b>Customer Types</b> &mdash; the discount groups. See the Partnership Pricing section.', 'tl' => '<b>Customer Types</b> &mdash; ang mga grupo ng discount. Tingnan ang Partnership Pricing na bahagi.'],
                            ['en' => '<b>Measurement Units</b> &mdash; piece, box, kilo and so on. A unit can be set to allow decimal quantities for goods sold by weight.', 'tl' => '<b>Measurement Units</b> &mdash; piece, box, kilo at iba pa. Pwedeng i-set ang unit na tumanggap ng desimal para sa mga tinitimbang na paninda.'],
                            ['en' => '<b>Payment Methods</b> &mdash; cash, GCash and others. Mark a method as requiring a reference number and the counter will insist on one.', 'tl' => '<b>Payment Methods</b> &mdash; cash, GCash at iba pa. Kapag minarkahan mong kailangan ng reference number, hindi papayag ang counter kung wala nito.'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
