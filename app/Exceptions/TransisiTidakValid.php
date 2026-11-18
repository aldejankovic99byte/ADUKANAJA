<?php

namespace App\Exceptions;

use DomainException;

/** Dilempar ketika perubahan status pengaduan tidak diizinkan oleh aturan workflow. Pesannya aman ditampilkan ke pengguna. */
class TransisiTidakValid extends DomainException
{
}
