<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    const SEPARATOR =  ".";

    const TYPE_CREATE = "create";
    const TYPE_READ = "read";
    const TYPE_UPDATE = "update";
    const TYPE_DELETE = "delete";
    const TYPE_ALL = [self::TYPE_CREATE, self::TYPE_READ, self::TYPE_UPDATE, self::TYPE_DELETE];
    const TRANSLATE_TYPE = [
        self::TYPE_CREATE => "Buat",
        self::TYPE_READ => "Lihat",
        self::TYPE_UPDATE => "Edit",
        self::TYPE_DELETE => "Hapus",
    ];

    const ROUTE_TYPE_CREATE = ['create', 'store'];
    const ROUTE_TYPE_READ = ['index', 'show', 'print', 'export', 'find'];
    const ROUTE_TYPE_UPDATE = ['edit', 'update'];
    const ROUTE_TYPE_DELETE = ['destroy'];

    const ACCESS_DASHBOARD = "dashboard";
    const ACCESS_USER = "user";
    const ACCESS_PERMISSION = "permission";
    const ACCESS_ROLE = "role";

    // Form Gensen
    const ACCESS_TEST_PAGE = 'test_page';
    const ACCESS_FILTER_ALL_PIC = 'filter_all_pic';
    const ACCESS_IMPORT_DATA_JEPANG = 'import_data_jepang';
    const ACCESS_MASTER_CLIENT = 'master_client';
    const ACCESS_MASTER_CLIENT_REGISTERED = 'master_client_registered';
    const ACCESS_CLIENT = 'client';
    const ACCESS_CLIENT_FORM_CONFIRMATION = 'client_form_confirmation';
    const ACCESS_POV_CLIENT = 'pov_client';
    const ACCESS_CLIENT_DOCUMENT = 'client_document';
    const ACCESS_CLIENT_AGREEMENT = 'client_agreement';
    const ACCESS_ADMIN_UPLOAD_DOCUMENT = 'admin_upload_document';
    const ACCESS_ADMIN_REVIEW = 'admin_review';
    const ACCESS_MONITORING_BUKTI_TRANSFER = 'monitoring_bukti_transfer';

    const ACCESS_ALL = [
        self::ACCESS_DASHBOARD,
        self::ACCESS_USER,
        self::ACCESS_PERMISSION,
        self::ACCESS_ROLE,

        // 
        self::ACCESS_FILTER_ALL_PIC,
        self::ACCESS_IMPORT_DATA_JEPANG,
        self::ACCESS_MASTER_CLIENT,
        self::ACCESS_MASTER_CLIENT_REGISTERED,
        self::ACCESS_CLIENT,
        self::ACCESS_CLIENT_FORM_CONFIRMATION,
        self::ACCESS_POV_CLIENT,
        self::ACCESS_CLIENT_DOCUMENT,
        self::ACCESS_ADMIN_UPLOAD_DOCUMENT,
        self::ACCESS_ADMIN_REVIEW,
        self::ACCESS_MONITORING_BUKTI_TRANSFER,
    ];

    const TRANSLATE_ACCESS = [
        self::ACCESS_DASHBOARD => "Dashboard",
        self::ACCESS_USER => "Pengguna",
        self::ACCESS_PERMISSION => "Akses",
        self::ACCESS_ROLE => "Jabatan",

        // 
        self::ACCESS_FILTER_ALL_PIC => 'Filter - Semua PIC',
        self::ACCESS_IMPORT_DATA_JEPANG => 'Import Data Jepang',
        self::ACCESS_MASTER_CLIENT => 'Master Data Klien',
        self::ACCESS_MASTER_CLIENT_REGISTERED => 'Master Klien Konfirmasi',
        self::ACCESS_POV_CLIENT => 'Dashboard POV Client',
        self::ACCESS_CLIENT => 'Master Klien',
        self::ACCESS_CLIENT_DOCUMENT => 'Document Klien',
        self::ACCESS_CLIENT_AGREEMENT => 'Surat Perjanjian Klien',
        self::ACCESS_ADMIN_UPLOAD_DOCUMENT => 'Admin Upload Dokumen',
        self::ACCESS_ADMIN_REVIEW => 'Admin Review',
        self::ACCESS_MONITORING_BUKTI_TRANSFER => 'Monitoring Bukti Transfer',
    ];

    const ACCESS_TYPE_ALL = [
        self::ACCESS_DASHBOARD => [self::TYPE_READ],
        self::ACCESS_USER => self::TYPE_ALL,
        self::ACCESS_ROLE => self::TYPE_ALL,
        self::ACCESS_PERMISSION => self::TYPE_ALL,

        self::ACCESS_FILTER_ALL_PIC => [self::TYPE_READ],
        self::ACCESS_IMPORT_DATA_JEPANG => self::TYPE_ALL,
        self::ACCESS_MASTER_CLIENT => self::TYPE_ALL,
        self::ACCESS_MASTER_CLIENT_REGISTERED => self::TYPE_ALL,
        self::ACCESS_CLIENT => self::TYPE_ALL,
        self::ACCESS_CLIENT_DOCUMENT => [self::TYPE_READ],
        self::ACCESS_CLIENT_AGREEMENT => [self::TYPE_READ],
        self::ACCESS_POV_CLIENT => [self::TYPE_READ],
        self::ACCESS_CLIENT_FORM_CONFIRMATION => self::TYPE_ALL,
        self::ACCESS_ADMIN_UPLOAD_DOCUMENT => self::TYPE_ALL,
        self::ACCESS_ADMIN_REVIEW => self::TYPE_ALL,
        self::ACCESS_MONITORING_BUKTI_TRANSFER => [self::TYPE_READ],
    ];

    /*
    | Parameters
    | permission (string) : merupakan nama dari permission
    */
    public static function translate($permission)
    {
        $explode = explode(self::SEPARATOR, $permission);
        $access = $explode[0];
        $type = $explode[1];

        $translateAccess = isset(self::TRANSLATE_ACCESS[$access]) ? self::TRANSLATE_ACCESS[$access] : $access;
        $translateType = isset(self::TRANSLATE_TYPE[$type]) ? self::TRANSLATE_TYPE[$type] : $type;

        return $translateAccess . " - " . $translateType;
    }

    /*
    | Parameters
    | access (string) : merupakan access yang tersedia pada helper ini
    | type (string) : merupakan type yang tersedia pada helper ini
    */
    public static function transform($access, $type)
    {
        return $access . self::SEPARATOR . $type;
    }

    /*
    | Parameters
    | permission (string) : merupakan nama dari permission
    */
    public static function getAccess($permission)
    {
        return explode(self::SEPARATOR, $permission)[0];
    }


    /*
    | Parameters
    | permission (string) : merupakan nama dari permission
    */
    public static function getTranslatedAccess($permission)
    {
        return self::TRANSLATE_ACCESS[self::getAccess($permission)];
    }


    /*
    | Parameters
    | permission (string) : merupakan nama dari permission
    */
    public static function getType($permission)
    {
        return explode(self::SEPARATOR, $permission)[1];
    }

    /*
    | Parameters
    | permission (string) : merupakan nama dari permission
    */
    public static function getTranslatedType($permission)
    {
        return self::TRANSLATE_TYPE[self::getType($permission)];
    }

    /*
    | Parameters
    | route_name (string) : Nama Route
    */
    public static function isRoutePermitted($route_name, $user = null)
    {
        // Identifikasi Route
        $exploded_route_names = explode(".", $route_name);
        $access = $exploded_route_names[0];
        $route_type = $exploded_route_names[1];

        if (in_array($route_type, self::ROUTE_TYPE_CREATE)) {
            $type = self::TYPE_CREATE;
        } else if (in_array($route_type, self::ROUTE_TYPE_READ)) {
            $type = self::TYPE_READ;
        } else if (in_array($route_type, self::ROUTE_TYPE_UPDATE)) {
            $type = self::TYPE_UPDATE;
        } else {
            $type = self::TYPE_DELETE;
        }

        // Pemeriksaan Hak Akses
        $user = $user == null ? User::find(Auth::id()) : $user;
        return $user->hasPermissionTo(self::transform($access, $type));
    }
}
