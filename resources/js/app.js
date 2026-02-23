import "./globals/theme.js"; /* By Sheaf.dev */
import "./globals/modals.js";
import "./bootstrap";
// Spatie
import "../../vendor/spatie/livewire-filepond/resources/dist/filepond";
import {
    Livewire,
    Alpine,
} from "../../vendor/livewire/livewire/dist/livewire.esm";
// Apex Charts
import ApexCharts from "apexcharts";

window.ApexCharts = ApexCharts;

Livewire.start();
