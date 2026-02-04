import "./globals/theme.js"; /* By Sheaf.dev */

import "./bootstrap";
import {
    Livewire,
    Alpine,
} from "../../vendor/livewire/livewire/dist/livewire.esm";
// Apex Charts
import ApexCharts from "apexcharts";

window.ApexCharts = ApexCharts;

Livewire.start();
