<?php

/*
 *
 * File ini bagian dari:
 *
 * OpenSID
 *
 * Sistem informasi desa sumber terbuka untuk memajukan desa
 *
 * Aplikasi dan source code ini dirilis berdasarkan lisensi GPL V3
 *
 * Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * Hak Cipta 2016 - 2025 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 *
 * Dengan ini diberikan izin, secara gratis, kepada siapa pun yang mendapatkan salinan
 * dari perangkat lunak ini dan file dokumentasi terkait ("Aplikasi Ini"), untuk diperlakukan
 * tanpa batasan, termasuk hak untuk menggunakan, menyalin, mengubah dan/atau mendistribusikan,
 * asal tunduk pada syarat berikut:
 *
 * Pemberitahuan hak cipta di atas dan pemberitahuan izin ini harus disertakan dalam
 * setiap salinan atau bagian penting Aplikasi Ini. Barang siapa yang menghapus atau menghilangkan
 * pemberitahuan ini melanggar ketentuan lisensi Aplikasi Ini.
 *
 * PERANGKAT LUNAK INI DISEDIAKAN "SEBAGAIMANA ADANYA", TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN
 * TERSIRAT. PENULIS ATAU PEMEGANG HAK CIPTA SAMA SEKALI TIDAK BERTANGGUNG JAWAB ATAS KLAIM, KERUSAKAN ATAU
 * KEWAJIBAN APAPUN ATAS PENGGUNAAN ATAU LAINNYA TERKAIT APLIKASI INI.
 *
 * @package   OpenSID
 * @author    Tim Pengembang OpenDesa
 * @copyright Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * @copyright Hak Cipta 2016 - 2025 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 * @license   http://www.gnu.org/licenses/gpl.html GPL V3
 * @link      https://github.com/OpenSID/OpenSID
 *
 */

defined('BASEPATH') || exit('No direct script access allowed');

/**
 * @property CI_Benchmark        $benchmark
 * @property CI_Config           $config
 * @property CI_DB_query_builder $db
 * @property CI_Form_validation  $form_validation
 * @property CI_Input            $input
 * @property CI_Lang             $lang
 * @property CI_Loader           $loader
 * @property CI_Log              $log
 * @property CI_Output           $output
 * @property CI_Router           $router
 * @property CI_Security         $security
 * @property CI_Session          $session
 * @property CI_URI              $uri
 * @property CI_Utf8             $utf8
 */
class Install extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Pastikan folder sessions ada untuk session storage
        $sessions_path = FCPATH . 'storage/framework/sessions';
        log_message('info', 'Installer constructor - sessions path: ' . $sessions_path . ', exists: ' . (is_dir($sessions_path) ? 'yes' : 'no'));
        
        if (!is_dir($sessions_path)) {
            if (!mkdir($sessions_path, 0755, true)) {
                log_message('error', 'Unable to create sessions directory: ' . $sessions_path);
            } else {
                log_message('info', 'Created sessions directory: ' . $sessions_path);
                // Tambahkan index.html untuk security
                file_put_contents($sessions_path . '/index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><p>Directory access is forbidden.</p></body></html>');
            }
        }
        
        // Set session save path ke folder yang kita buat
        ini_set('session.save_path', $sessions_path);
        
        // Load session dengan konfigurasi khusus untuk installer
        $session_config = [
            'sess_driver' => 'files',
            'sess_cookie_name' => 'opensid_installer',
            'sess_expiration' => 3600, // 1 hour
            'sess_save_path' => $sessions_path,
            'sess_match_ip' => false,
            'sess_time_to_update' => 300,
            'sess_regenerate_destroy' => false,
        ];
        
        $this->load->library('session', $session_config);
        $this->load->config('installer');
        $this->folder_lainnya();
    }

    /**
     * Step 1
     */
    public function index()
    {
        $this->session->instalasi = true;

        // disable install
        if (file_exists(DESAPATH)) {
            show_404();
        }

        return view('installer.steps.welcome');
    }

    /**
     * Step 2
     */
    public function server()
    {
        // disable install
        if (file_exists(DESAPATH)) {
            show_404();
        }

        return view('installer.steps.server', [
            'result' => $this->check_server(),
        ]);
    }

    private function check_server(): bool
    {
        foreach ($this->config->item('server') as $check) {
            if (! $check['check']()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Step 3
     */
    public function folders()
    {
        // disable install
        if (file_exists(DESAPATH)) {
            show_404();
        }

        if (! $this->check_server()) {
            return redirect('install/server');
        }

        return view('installer.steps.folders', [
            'result' => $this->check_folders(),
        ]);
    }

    private function check_folders(): bool
    {
        foreach ($this->config->item('folders') as $check) {
            if (! $check['check']()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Step 4
     */
    public function database()
    {
        // disable install
        if (file_exists(DESAPATH)) {
            show_404();
        }

        $server_ok = $this->check_server();
        $folders_ok = $this->check_folders();
        
        log_message('info', 'Server check: ' . ($server_ok ? 'PASS' : 'FAIL'));
        log_message('info', 'Folders check: ' . ($folders_ok ? 'PASS' : 'FAIL'));
        
        if (!$server_ok || !$folders_ok) {
            log_message('info', 'Redirecting to install/folders due to failed checks');
            return redirect('install/folders');
        }

        log_message('info', 'Database method called: ' . $this->input->method());
        
        if ($this->input->method() === 'get') {
            return view('installer.steps.database');
        }

        $this->form_validation->set_error_delimiters(
            '<span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">',
            '</span>'
        );

        $this->form_validation
            ->set_rules('database_hostname', 'Database host', 'required')
            ->set_rules('database_port', 'Database port', 'required|integer')
            ->set_rules('database_name', 'Database name', 'required')
            ->set_rules('database_username', 'Database username', 'required');

        if (! $this->form_validation->run()) {
            log_message('error', 'Form validation failed: ' . json_encode($this->form_validation->error_array()));
            return view('installer.steps.database');
        }
        
        log_message('info', 'Form validation passed, proceeding with database connection test');

        try {
            $hostname = $this->input->post('database_hostname');
            $port = $this->input->post('database_port') ?: 3306;
            $dbname = $this->input->post('database_name');
            $username = $this->input->post('database_username');
            $password = $this->input->post('database_password');
            
            log_message('info', 'PDO Connection attempt: ' . json_encode([
                'hostname' => $hostname,
                'port' => $port,
                'database' => $dbname,
                'username' => $username
            ]));
            
            // Try connection tanpa database name dulu untuk test server
            $dsn_server = "mysql:host={$hostname};port={$port}";
            $connection_test = new PDO($dsn_server, $username, $password);
            $connection_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Test apakah database exists
            $stmt = $connection_test->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$dbname]);
            if (!$stmt->fetch()) {
                throw new Exception("Database '{$dbname}' tidak ditemukan");
            }
            
            // Connection ke database spesifik
            $dsn = "mysql:host={$hostname};port={$port};dbname={$dbname}";
            $connection = new PDO($dsn, $username, $password);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            log_message('info', 'PDO Connection successful');
            
        } catch (Exception $e) {
            log_message('error', 'PDO Connection failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
            $this->session->set_flashdata('errors', 'Koneksi database gagal: ' . $e->getMessage());

            return redirect('install/database');
        }

        try {
            $config = $this->config_database($this->input->post());
            log_message('info', 'Database config: ' . json_encode([
                'hostname' => $config['hostname'],
                'port' => $config['port'],
                'database' => $config['database'],
                'username' => $config['username'],
                'dbdriver' => $config['dbdriver']
            ]));
            
            // Load database dengan return object
            $db = $this->load->database($config, true);
            
            // Test koneksi dengan query simple
            if ($db && method_exists($db, 'query')) {
                $result = $db->query('SELECT 1');
                if (!$result) {
                    throw new Exception('Database connection test failed');
                }
            } else {
                throw new Exception('Database object not created properly');
            }
            
        } catch (Exception $e) {
            log_message('error', 'Database connection failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
            $this->session->set_flashdata('errors', 'Tidak berhasil terkoneksi ke database: ' . $e->getMessage());

            return redirect('install/database');
        }

        // Simpan konfigurasi database ke session untuk langkah berikutnya  
        // Menggunakan key yang sama dengan config_database() method
        $db_config = [
            'hostname' => $this->input->post('database_hostname'),
            'port' => $this->input->post('database_port'),
            'database' => $this->input->post('database_name'),
            'username' => $this->input->post('database_username'),
            'password' => $this->input->post('database_password')
        ];
        
        // Set flag instalasi dan database config
        $this->session->set_userdata('instalasi', true);
        $this->session->set_userdata($db_config);
        
        // Backup database config ke file sebagai fallback
        $backup_file = FCPATH . 'storage/framework/installer_db_config.tmp';
        file_put_contents($backup_file, json_encode($db_config));
        
        // Backup juga ke cookies sebagai triple fallback
        $this->input->set_cookie('installer_hostname', $db_config['hostname'], 3600);
        $this->input->set_cookie('installer_database', $db_config['database'], 3600);
        $this->input->set_cookie('installer_username', $db_config['username'], 3600);
        $this->input->set_cookie('installer_password', base64_encode($db_config['password']), 3600);
        $this->input->set_cookie('installer_port', $db_config['port'], 3600);
        
        // Log semua session data untuk debugging
        log_message('info', 'Database success - All session data after save: ' . json_encode($this->session->all_userdata()));
        log_message('info', 'Session ID after save: ' . $this->session->session_id);
        

        // Langsung redirect ke migrations karena koneksi sudah berhasil
        return redirect('install/migrations');
    }

    private function config_database(array $request = []): array
    {
        if (! $this->session->has_userdata('hostname') && isset($request['database_hostname'])) {
            $this->session->set_userdata([
                'hostname' => $request['database_hostname'],
                'port'     => $request['database_port'],
                'username' => $request['database_username'],
                'password' => $request['database_password'],
                'database' => $request['database_name'],
            ]);
        }

        $db = '$db';

        $this->config->set_item(
            'database',
            <<<EOS
                <?php
                // -------------------------------------------------------------------------
                //
                // Letakkan username, password dan database sebetulnya di file ini.
                // File ini JANGAN di-commit ke GIT. TAMBAHKAN di .gitignore
                // -------------------------------------------------------------------------

                // Data Konfigurasi MySQL yang disesuaikan

                {$db}['default']['hostname'] = '{$this->session->hostname}';
                {$db}['default']['username'] = '{$this->session->username}';
                {$db}['default']['password'] = '{$this->session->password}';
                {$db}['default']['port']     = {$this->session->port};
                {$db}['default']['database'] = '{$this->session->database}';
                {$db}['default']['dbcollat'] = 'utf8mb4_general_ci';

                /*
                | Untuk setting koneksi database 'Strict Mode'
                | Sesuaikan dengan ketentuan hosting
                */
                {$db}['default']['stricton'] = false;
                EOS
        );

        return [
            'dsn'      => '',
            'hostname' => $this->session->hostname,
            'port'     => $this->session->port,
            'username' => $this->session->username,
            'password' => $this->session->password,
            'database' => $this->session->database,
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => false,
            'db_debug' => true,
            'cache_on' => false,
            'cachedir' => '',
            'char_set' => 'utf8mb4',
            'dbcollat' => 'utf8mb4_general_ci',
            'swap_pre' => '',
            'encrypt'  => false,
            'compress' => false,
            'stricton' => false,
            'failover' => [],
        ];
    }

    /**
     * Step 5
     */
    public function migrations()
    {
        // disable install
        if (file_exists(DESAPATH)) {
            show_404();
        }

        // Cek konfigurasi database dari file backup terlebih dahulu (tidak bergantung session/cookies)
        $backup_file = FCPATH . 'storage/framework/installer_db_config.tmp';
        $db_config_valid = false;
        
        if (file_exists($backup_file)) {
            $backup_config = json_decode(file_get_contents($backup_file), true);
            if ($backup_config && isset($backup_config['hostname'], $backup_config['database'], $backup_config['username'])) {
                // Restore dari backup
                $this->session->set_userdata($backup_config);
                $db_config_valid = true;
            }
        }
        
        // Fallback ke session jika file backup tidak ada
        if (!$db_config_valid) {
            $db_config_valid = $this->session->hostname && $this->session->database && $this->session->username;
        }
        
        if (!$db_config_valid) {
            $this->session->set_flashdata('errors', 'Konfigurasi database tidak ditemukan. Silakan ulangi konfigurasi database.');
            return redirect('install/database');
        }

        try {
            $this->load->database($this->config_database());
            
            // Test koneksi database
            if (!$this->db || !$this->db->initialize()) {
                throw new Exception('Unable to initialize database connection');
            }
            
            log_message('info', 'Database loaded successfully for migrations');
        } catch (Exception $e) {
            log_message('error', 'Database connection failed in migrations: ' . $e->getMessage());
            return redirect('install/database');
        }

        if (!$this->check_server() || !$this->check_folders()) {
            log_message('error', 'Server or folders check failed in migrations');
            return redirect('install/database');
        }

        if ($this->input->method() === 'get') {
            return view('installer.steps.migrations');
        }

        try {
            folder_desa();

            app()->configure('database');

            $this->load->model('seeders/seeder');

            return redirect('install/user');
        } catch (Exception $e) {
            log_message('error', $e);
            $this->session->set_flashdata('errors', $e->getMessage());

            return redirect('install/migrations');
        }
    }

    /**
     * Step 6
     */
    public function user()
    {
        $this->load->database();

        if (
            ! $this->db
            || ! file_exists(DESAPATH)
            || ! $this->check_server()
            || ! $this->check_folders()
        ) {
            return redirect('install/migrations');
        }

        // disable install jika sudah mengubah password default
        if (! password_verify('sid304', $this->db->where('config_id', identitas('id'))->get('user')->row()->password)) {
            show_404();
        }

        if ($this->input->method() === 'get') {
            return view('installer.steps.user');
        }

        $this->form_validation->set_error_delimiters(
            '<span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">',
            '</span>'
        );

        $this->form_validation
            ->set_rules('username', 'Username', 'required')
            ->set_rules('password', 'Password', 'required|callback_syarat_sandi')
            ->set_rules('confirm_password', 'Konfirmasi Password', 'required|matches[password]');

        if (! $this->form_validation->run()) {
            return view('installer.steps.user');
        }

        $this->db->where('config_id', identitas('id'))->where('username', 'admin')->update('user', [
            'username' => $this->input->post('username'),
            'password' => generatePasswordHash($this->input->post('password')),
        ]);

        return redirect('install/finish');
    }

    /**
     * Step 7
     */
    public function finish(): void
    {
        $this->session->unset_userdata([
            'errors',
            'hostname',
            'port',
            'username',
            'password',
            'database',
            'instalasi',
        ]);

        // Hapus cookies installer yang mungkin menyebabkan konflik
        $this->input->set_cookie('installer_hostname', '', -1);
        $this->input->set_cookie('installer_database', '', -1);
        $this->input->set_cookie('installer_username', '', -1);
        $this->input->set_cookie('installer_password', '', -1);
        $this->input->set_cookie('installer_port', '', -1);
        
        // Hapus file backup installer
        $backup_file = FCPATH . 'storage/framework/installer_db_config.tmp';
        if (file_exists($backup_file)) {
            unlink($backup_file);
        }

        // Clear all cookies untuk mencegah konflik
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'installer_') === 0 || $name === 'opensid_installer') {
                setcookie($name, '', time() - 3600, '/');
            }
        }

        // Redirect ke halaman login admin dengan pesan sukses
        $this->session->set_flashdata('success', 'Instalasi OpenSID berhasil! Silakan login dengan username dan password yang telah Anda buat.');
        redirect('siteman');
    }

    public function syarat_sandi($password)
    {
        if (! preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}$/', (string) $password)) {
            $this->form_validation->set_message('syarat_sandi', SYARAT_SANDI);

            return false;
        }

        return true;
    }

    public function folder_lainnya(): void
    {
        foreach (config_item('lainnya') as $folder => $lainnya) {
            folder($folder, $lainnya[0], $lainnya[1], $lainnya[2] ?? []);
        }

        copyFavicon();
    }
}
