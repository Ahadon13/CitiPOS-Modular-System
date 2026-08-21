<?php

declare(strict_types=1);

namespace App\Support\Documentation;

final class MotorShop
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'who',
                'en' => 'Who works here',
                'tl' => 'Sino ang nagtatrabaho dito',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Motor Shop Cashier, Chief Mechanic and Mechanic roles all have access to this module. What each person can actually open still depends on their individual permissions.',
                        'tl' => 'May access sa module na ito ang Motor Shop Cashier, Chief Mechanic at Mechanic. Nakadepende pa rin sa indibidwal na permissions kung ano talaga ang mabubuksan ng bawat isa.',
                    ],
                    [
                        'type' => 'text',
                        'en' => 'The big difference from the other modules is that a single sale can carry both <b>parts</b> and <b>labour</b>.',
                        'tl' => 'Ang malaking pagkakaiba sa ibang module ay pwedeng may <b>parts</b> at <b>labor</b> sa iisang benta.',
                    ],
                ],
            ],
            [
                'id' => 'pos',
                'en' => 'Selling parts',
                'tl' => 'Pagbenta ng parts',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'The toolbar carries <b>Price Check</b>, <b>Barcode Scan</b> and <b>Add Service</b>, then the parts search.',
                        'tl' => 'Nasa toolbar ang <b>Price Check</b>, <b>Barcode Scan</b> at <b>Add Service</b>, tapos ang parts search.',
                    ],
                    [
                        'type' => 'image',
                        'src' => 'pos-motorshop-payment.png',
                        'en' => 'The motor shop counter with a part in the cart and Process Payment open. Note the cart totals split Products and Services.',
                        'tl' => 'Ang motor shop counter na may part sa cart at bukas ang Process Payment. Pansinin na hiwalay ang Products at Services sa totals ng cart.',
                    ],
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'Choose the customer first &mdash; <b>Walk-in</b>, or switch to <b>Customer</b>.', 'tl' => 'Piliin muna ang customer &mdash; <b>Walk-in</b>, o lumipat sa <b>Customer</b>.'],
                            ['en' => 'Search for the part. You can search by name, <b>part number</b> or <b>OEM number</b>, or filter by category.', 'tl' => 'Hanapin ang part. Pwede sa pangalan, <b>part number</b> o <b>OEM number</b>, o i-filter ayon sa category.'],
                            ['en' => 'Check the fitment details on the row &mdash; part number, vehicle model, engine type, year range &mdash; against the customer vehicle.', 'tl' => 'Tingnan ang fitment details sa row &mdash; part number, vehicle model, engine type, year range &mdash; laban sa sasakyan ng customer.'],
                            ['en' => 'Pick the packaging if there is more than one, then click the row to add it.', 'tl' => 'Piliin ang packaging kung mahigit isa, tapos i-click ang row para maidagdag.'],
                            ['en' => 'Add any labour &mdash; see the next section.', 'tl' => 'Idagdag ang labor &mdash; tingnan ang susunod na bahagi.'],
                            ['en' => 'Press <b>Checkout</b> or <kbd>F4</kbd> and take payment.', 'tl' => 'Pindutin ang <b>Checkout</b> o <kbd>F4</kbd> at tanggapin ang bayad.'],
                        ],
                    ],
                    [
                        'type' => 'note',
                        'en' => 'Fill in the fitment fields properly when you create a part. They are searchable and they show at the counter, which is what makes finding the right part fast.',
                        'tl' => 'Punan nang maayos ang fitment fields kapag gumagawa ng part. Nahahanap ang mga ito sa search at lumalabas sa counter, kaya mas mabilis makita ang tamang part.',
                    ],
                ],
            ],
            [
                'id' => 'services',
                'en' => 'Adding labour and assigning a mechanic',
                'tl' => 'Pagdagdag ng labor at pag-assign ng mechanic',
                'blocks' => [
                    [
                        'type' => 'steps',
                        'items' => [
                            ['en' => 'Click <b>Add Service</b> in the toolbar.', 'tl' => 'I-click ang <b>Add Service</b> sa toolbar.'],
                            ['en' => 'Enter the <b>service name</b> &mdash; what the job is, for example Change Oil or Brake Pad Replacement.', 'tl' => 'Ilagay ang <b>service name</b> &mdash; kung anong trabaho, halimbawa Change Oil o Brake Pad Replacement.'],
                            ['en' => 'Enter the <b>price</b>, and the quantity if you are charging more than one unit of it.', 'tl' => 'Ilagay ang <b>presyo</b>, at ang dami kung mahigit isang unit ang sinisingil mo.'],
                            ['en' => 'Assign the <b>mechanic</b> who did the work. Only mechanics attached to this branch appear in the list.', 'tl' => 'I-assign ang <b>mechanic</b> na gumawa. Ang mga mechanic lang na naka-attach sa branch na ito ang lalabas sa listahan.'],
                            ['en' => 'Add a description if the job needs explaining on the receipt, then save.', 'tl' => 'Magdagdag ng deskripsyon kung kailangang ipaliwanag ang trabaho sa resibo, tapos i-save.'],
                        ],
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => 'A sale can be parts only, labour only, or both.', 'tl' => 'Pwedeng parts lang, labor lang, o pareho ang isang benta.'],
                            ['en' => 'Services carry no stock and no cost, so they are pure revenue and lift your margin.', 'tl' => 'Walang stock at walang cost ang services, kaya purong kita ito at nagpapataas ng margin mo.'],
                            ['en' => 'The cart totals show <b>Products</b> and <b>Services</b> on separate lines before the net total.', 'tl' => 'Ipinapakita ng cart totals ang <b>Products</b> at <b>Services</b> sa magkahiwalay na linya bago ang net total.'],
                        ],
                    ],
                    [
                        'type' => 'warn',
                        'en' => 'Always assign the mechanic. Leave it blank and the job still sells, but it will not appear against anyone in the services report &mdash; so nobody gets credit for the work.',
                        'tl' => 'Palaging i-assign ang mechanic. Kung iiwan mong blangko, mabebenta pa rin ang trabaho, pero hindi ito lalabas sa kahit sino sa services report &mdash; kaya walang makakakuha ng kredito sa ginawa.',
                    ],
                ],
            ],
            [
                'id' => 'inventory',
                'en' => 'Parts inventory',
                'tl' => 'Imbentaryo ng parts',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Products, Stocks, Purchases, Sales, Customers and Expenses behave as described in the <b>Pharmacist</b> section. These are the motor-shop specifics.',
                        'tl' => 'Ang Products, Stocks, Purchases, Sales, Customers at Expenses ay gumagana gaya ng nakasaad sa <b>Pharmacist</b> na bahagi. Ito ang mga partikular sa motor shop.',
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => 'When creating a part you also fill in <b>part number</b>, <b>vehicle model</b>, <b>engine type</b>, <b>year range</b> and <b>OEM number</b>.', 'tl' => 'Sa paggawa ng part, pupunan mo rin ang <b>part number</b>, <b>vehicle model</b>, <b>engine type</b>, <b>year range</b> at <b>OEM number</b>.'],
                            ['en' => 'A product image is genuinely useful here for telling similar-looking parts apart.', 'tl' => 'Talagang kapaki-pakinabang dito ang larawan ng produkto para makilala ang magkakamukhang parts.'],
                            ['en' => 'Mark parts you never keep on the shelf as <b>Special Order</b>. They can then be sold with zero stock and the purchase order is raised for you.', 'tl' => 'Markahan bilang <b>Special Order</b> ang mga part na hindi mo iniistock. Mabebenta ang mga ito kahit walang stock at kusang gagawa ng purchase order.'],
                            ['en' => 'Expiry is not emphasised in this module, since parts do not expire.', 'tl' => 'Hindi binibigyang-diin ang expiry sa module na ito, dahil hindi nag-e-expire ang parts.'],
                            ['en' => 'Barcode scanning <b>to the cart</b> is not enabled for motor shop. <b>Price Check</b> still works for looking parts up.', 'tl' => 'Hindi naka-enable ang barcode scanning <b>papunta sa cart</b> para sa motor shop. Gumagana pa rin ang <b>Price Check</b> para maghanap ng parts.'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'reports',
                'en' => 'Services reporting',
                'tl' => 'Reporting ng services',
                'blocks' => [
                    [
                        'type' => 'text',
                        'en' => 'Where the pharmacy has a selling price list, motor shop has a <b>Services</b> breakdown instead. Open <b>Reports</b> and scroll to it.',
                        'tl' => 'Kung ang pharmacy ay may listahan ng presyo, ang motor shop naman ay may <b>Services</b> na breakdown. Buksan ang <b>Reports</b> at mag-scroll dito.',
                    ],
                    [
                        'type' => 'list',
                        'items' => [
                            ['en' => 'Job counts and labour revenue for the period.', 'tl' => 'Bilang ng trabaho at kita mula sa labor para sa panahong iyon.'],
                            ['en' => 'Searchable, and filterable <b>by mechanic</b>, so you can see who brought in what.', 'tl' => 'Pwedeng hanapin, at i-filter <b>ayon sa mechanic</b>, para makita kung sino ang may dalang kita.'],
                            ['en' => 'The rest of the report &mdash; revenue, expenses, gross and net profit, stock movement &mdash; works the same as every other module.', 'tl' => 'Ang iba pang bahagi ng report &mdash; kita, gastos, gross at net profit, galaw ng stock &mdash; ay pareho sa lahat ng module.'],
                        ],
                    ],
                    [
                        'type' => 'note',
                        'en' => 'Record mechanic wages under <b>Expenses &rarr; Payroll</b>. Their labour is revenue and their wages are cost; record both and the reports show what the workshop actually earns.',
                        'tl' => 'Itala ang sahod ng mechanic sa <b>Expenses &rarr; Payroll</b>. Kita ang labor nila at gastos ang sahod nila; itala pareho at ipapakita ng reports kung magkano talaga ang kinikita ng shop.',
                    ],
                ],
            ],
        ];
    }
}
