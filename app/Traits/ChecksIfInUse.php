<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Str;

trait ChecksIfInUse
{
    /**
     * Checks if the model is currently being referenced in any of the provided relationships.
     * * @param array $relationships Array of relationship method names (e.g., ['products', 'purchases'])
     * @return string|false Returns an error message if in use, or false if it is safe to delete.
     */
    public function checkInUse(array $relationships): string|false
    {
        foreach ($relationships as $relation) {

            if (!method_exists($this, $relation)) {
                throw new \Exception(" Developer Error: The relationship '{$relation}' does not exist on the " . class_basename($this) . " model.");
            }

            // If the relationship exists and has at least one record
            if ($this->{$relation}()->exists()) {

                // Format the relation name beautifully (e.g., 'purchaseItems' becomes 'purchase items')
                $friendlyName = str_replace('_', ' ', Str::snake($relation));

                return "Cannot delete. This record is currently in use by associated {$friendlyName}.";
            }
        }

        // Safe to delete!
        return false;
    }
}
