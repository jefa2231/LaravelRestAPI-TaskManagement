<?php

return [
    /*
     * Lokasi penyimpanan JSON. Default storage/app/tasks.json.
     * Ubah lewat TASKS_STORE_PATH saat pengujian agar data asli tidak tersentuh.
     */
    'path' => env('TASKS_STORE_PATH', storage_path('app/tasks.json')),
];
