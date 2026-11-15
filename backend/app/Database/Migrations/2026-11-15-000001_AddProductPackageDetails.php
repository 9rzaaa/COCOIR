<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProductPackageDetails extends Migration
{
    public function up()
    {
        $this->forge->addColumn('products', [
            'package_contents' => ['type' => 'TEXT', 'null' => true],
            'specifications' => ['type' => 'TEXT', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('products', ['package_contents', 'specifications']);
    }
}
