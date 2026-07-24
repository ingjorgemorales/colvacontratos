<?php
namespace App\Controllers;
use App\Core\Auth; use App\Core\View; use App\Core\Flash; use App\Models\Admin; use App\Services\Mailer;

final class AdminController {
    private function base(string $section, array $data=[]): void { Auth::requireLogin(); View::render('admin/'.$section, $data + ['section'=>$section]); }
    public function panel(): void { Auth::requireLogin(); View::render('admin/panel', ['section'=>'panel']); }
    public function users(): void { $this->base('users', ['users'=>Admin::users(), 'roles'=>Admin::roles()]); }
    public function userStore(): void {
        Auth::requireLogin();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');

        // Validaciones amigables antes de insertar.
        if ($name === '' || $email === '' || $cedula === '') {
            Flash::set('danger','Nombre, correo y cédula son obligatorios.');
            header('Location: index.php?r=admin.users'); exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('danger','El correo electrónico no es válido.');
            header('Location: index.php?r=admin.users'); exit;
        }
        if (!preg_match('/^[0-9]{5,15}$/', $cedula)) {
            Flash::set('danger','La cédula debe tener solo números (entre 5 y 15 dígitos).');
            header('Location: index.php?r=admin.users'); exit;
        }
        if (Admin::emailExists($email)) {
            Flash::set('danger','Ya existe un usuario con ese correo.');
            header('Location: index.php?r=admin.users'); exit;
        }
        if (Admin::cedulaExists($cedula)) {
            Flash::set('danger','Ya existe un usuario con esa cédula.');
            header('Location: index.php?r=admin.users'); exit;
        }

        $password = Admin::createUser($_POST);
        if ($password === null) {
            Flash::set('danger','Nombre, correo y cédula son obligatorios.');
            header('Location: index.php?r=admin.users');
            exit;
        }

        $loginUrl = 'https://colvacontratos.colvatel.com/index.php?r=login';
        $subject = 'Bienvenido a ColvaContratos - Tus credenciales de acceso';
        $body = "Hola {$name},\n\nSe ha creado tu cuenta en ColvaContratos.\n\nUsuario (email): {$email}\nContrasena temporal: {$password}\n\nPuedes ingresar en: {$loginUrl}\n\nPor seguridad, al iniciar sesion por primera vez el sistema te pedira crear una contrasena nueva.\n";
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Mailer::send($email, $subject, $body);
        }

        Flash::set('success','Usuario creado correctamente.');
        header('Location: index.php?r=admin.users');
        exit;
    }
    public function userUpdate(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        Admin::updateUser($id, $_POST);

        Flash::set('success','Usuario actualizado.');
        header('Location: index.php?r=admin.users');
        exit;
    }
    public function providers(): void { $this->base('providers', ['providers'=>Admin::providers(trim($_GET['q']??'')), 'q'=>trim($_GET['q']??'')]); }
    public function providerStore(): void { Auth::requireLogin(); Admin::createProvider($_POST); Flash::set('success','Proveedor creado correctamente.'); header('Location: index.php?r=admin.providers'); exit; }
    public function providerUpdate(): void { Auth::requireLogin(); Admin::updateProvider((int)($_GET['id']??0), $_POST); Flash::set('success','Proveedor actualizado.'); header('Location: index.php?r=admin.providers'); exit; }
    public function supervisors(): void { $this->base('supervisors', ['supervisors'=>Admin::supervisors(), 'users'=>Admin::users()]); }
    public function supervisorStore(): void { Auth::requireLogin(); Admin::createSupervisor($_POST); Flash::set('success','Supervisor creado correctamente.'); header('Location: index.php?r=admin.supervisors'); exit; }
    public function supervisorUpdate(): void { Auth::requireLogin(); Admin::updateSupervisor((int)($_GET['id']??0), $_POST); Flash::set('success','Supervisor actualizado.'); header('Location: index.php?r=admin.supervisors'); exit; }
    public function catalogs(): void { $gid=(string)($_GET['group_id']??''); $q=trim((string)($_GET['q']??'')); $this->base('catalogs', ['groups'=>Admin::groups(), 'items'=>Admin::items($gid,$q), 'group_id'=>$gid, 'q'=>$q, 'categoryCards'=>Admin::categoryCards(), 'stats'=>Admin::catalogStats()]); }
    public function groupStore(): void { Auth::requireLogin(); Admin::createGroup($_POST); Flash::set('success','Grupo creado.'); header('Location: index.php?r=admin.catalogs'); exit; }
    public function itemStore(): void { Auth::requireLogin(); Admin::createItem($_POST); Flash::set('success','Campo creado.'); header('Location: index.php?r=admin.catalogs&group_id='.(string)($_POST['group_id']??'')); exit; }
    public function itemUpdate(): void { Auth::requireLogin(); Admin::updateItem((int)($_GET['id']??0), $_POST); Flash::set('success','Campo actualizado.'); header('Location: index.php?r=admin.catalogs&group_id='.(string)($_POST['group_id']??'')); exit; }
    public function assign(): void { $this->base('assign', ['users'=>Admin::users()]); }
}
