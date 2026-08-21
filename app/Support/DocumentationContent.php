<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Documentation\Administration;
use App\Support\Documentation\GettingStarted;
use App\Support\Documentation\Grocery;
use App\Support\Documentation\MotorShop;
use App\Support\Documentation\Partnership;
use App\Support\Documentation\Pharmacist;
use App\Support\Documentation\Reference;

/**
 * Content for the public user manual at /documentation.
 *
 * Every string carries both an English and a Tagalog version so the page can
 * switch language without a second source of truth drifting out of date.
 * UI labels (button and menu names) stay in English inside the Tagalog copy,
 * because that is what is actually printed on the screen the reader is looking
 * at -- translating them would send people hunting for a button that does not
 * exist.
 */
final class DocumentationContent
{
    /**
     * Top-level categories, in reading order.
     *
     * @return list<array{id:string,icon:string,en:string,tl:string,blurb_en:string,blurb_tl:string}>
     */
    public static function categories(): array
    {
        return [
            [
                'id' => 'getting-started',
                'icon' => 'rocket-launch',
                'en' => 'Getting Started',
                'tl' => 'Pagsisimula',
                'blurb_en' => 'Signing in, choosing your branch, and finding your way around.',
                'blurb_tl' => 'Pag-sign in, pagpili ng branch, at paglibot sa sistema.',
            ],
            [
                'id' => 'administration',
                'icon' => 'shield-check',
                'en' => 'Administration',
                'tl' => 'Administrasyon',
                'blurb_en' => 'For owners and admins: branches, staff accounts, permissions and business reports.',
                'blurb_tl' => 'Para sa may-ari at admin: mga branch, account ng staff, permissions at business reports.',
            ],
            [
                'id' => 'pharmacist',
                'icon' => 'beaker',
                'en' => 'Pharmacist',
                'tl' => 'Pharmacist',
                'blurb_en' => 'The pharmacy counter and pharmacy inventory, screen by screen.',
                'blurb_tl' => 'Ang pharmacy counter at pharmacy inventory, bawat screen.',
            ],
            [
                'id' => 'grocery',
                'icon' => 'shopping-cart',
                'en' => 'Grocery Cashier',
                'tl' => 'Grocery Cashier',
                'blurb_en' => 'The grocery counter and grocery inventory.',
                'blurb_tl' => 'Ang grocery counter at grocery inventory.',
            ],
            [
                'id' => 'motorshop',
                'icon' => 'wrench-screwdriver',
                'en' => 'Motor Shop',
                'tl' => 'Motor Shop',
                'blurb_en' => 'Selling parts and labour together, and assigning mechanics.',
                'blurb_tl' => 'Pagbenta ng parts at labor nang sabay, at pag-assign ng mechanic.',
            ],
            [
                'id' => 'partnership',
                'icon' => 'user-group',
                'en' => 'Partnership Pricing',
                'tl' => 'Partnership Pricing',
                'blurb_en' => 'LGU, DSWD and other mandated prices: set them up, sell with them, report on them.',
                'blurb_tl' => 'LGU, DSWD at iba pang mandated na presyo: paano i-set, ibenta, at i-report.',
            ],
            [
                'id' => 'reference',
                'icon' => 'bookmark-square',
                'en' => 'Reference',
                'tl' => 'Sanggunian',
                'blurb_en' => 'Shortcuts, roles, vocabulary, and what to check when something looks wrong.',
                'blurb_tl' => 'Mga shortcut, role, bokabularyo, at ano ang titingnan kapag may mukhang mali.',
            ],
        ];
    }

    /**
     * Sections for one category, or an empty list for an unknown id.
     *
     * @return list<array<string, mixed>>
     */
    public static function sections(string $categoryId): array
    {
        return match ($categoryId) {
            'getting-started' => GettingStarted::sections(),
            'administration' => Administration::sections(),
            'pharmacist' => Pharmacist::sections(),
            'grocery' => Grocery::sections(),
            'motorshop' => MotorShop::sections(),
            'partnership' => Partnership::sections(),
            'reference' => Reference::sections(),
            default => [],
        };
    }

    public static function isValidCategory(string $categoryId): bool
    {
        return in_array($categoryId, array_column(self::categories(), 'id'), true);
    }

    /**
     * @return array{en: string, tl: string}
     */
    public static function languages(): array
    {
        return ['en' => 'English', 'tl' => 'Tagalog'];
    }
}
