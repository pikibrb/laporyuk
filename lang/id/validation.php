<?php

return [
    'required' => 'Kolom :attribute wajib diisi.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
    'integer' => 'Kolom :attribute harus berupa angka bulat.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'image' => 'Kolom :attribute harus berupa gambar.',
    'mimes' => 'Kolom :attribute harus berupa berkas dengan tipe: :values.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'lowercase' => 'Kolom :attribute harus berupa huruf kecil.',
    'unique' => 'Kolom :attribute sudah digunakan.',
    'exists' => 'Kolom :attribute yang dipilih tidak valid.',
    'in' => 'Kolom :attribute yang dipilih tidak valid.',
    'regex' => 'Format kolom :attribute tidak valid.',
    'current_password' => 'Kata sandi yang dimasukkan salah.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh lebih dari :max item.',
        'file' => 'Kolom :attribute tidak boleh lebih besar dari :max kilobyte.',
        'numeric' => 'Kolom :attribute tidak boleh lebih besar dari :max.',
        'string' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'array' => 'Kolom :attribute harus memiliki setidaknya :min item.',
        'file' => 'Kolom :attribute harus berukuran setidaknya :min kilobyte.',
        'numeric' => 'Kolom :attribute harus setidaknya :min.',
        'string' => 'Kolom :attribute harus setidaknya :min karakter.',
    ],
    'between' => [
        'array' => 'Kolom :attribute harus memiliki antara :min dan :max item.',
        'file' => 'Kolom :attribute harus berukuran antara :min dan :max kilobyte.',
        'numeric' => 'Kolom :attribute harus antara :min dan :max.',
        'string' => 'Kolom :attribute harus antara :min dan :max karakter.',
    ],
];
