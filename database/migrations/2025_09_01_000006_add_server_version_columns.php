<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServerVersionColumns extends Migration
{
    public function up()
    {
        // Add a monotonic server_version to transactions for robust conflict detection
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('transactions', 'server_version')) {
                    $table->unsignedBigInteger('server_version')->default(1)->after('updated_at');
                }
            });
        }

        // Keep last-known server_version on mappings so clients can round-trip versions
        if (Schema::hasTable('mobile_sync_mappings')) {
            Schema::table('mobile_sync_mappings', function (Blueprint $table) {
                if (! Schema::hasColumn('mobile_sync_mappings', 'server_version')) {
                    $table->unsignedBigInteger('server_version')->nullable()->after('server_id')->index();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (Schema::hasColumn('transactions', 'server_version')) {
                    $table->dropColumn('server_version');
                }
            });
        }

        if (Schema::hasTable('mobile_sync_mappings')) {
            Schema::table('mobile_sync_mappings', function (Blueprint $table) {
                if (Schema::hasColumn('mobile_sync_mappings', 'server_version')) {
                    $table->dropColumn('server_version');
                }
            });
        }
    }
}
