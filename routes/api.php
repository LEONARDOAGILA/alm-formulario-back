<?php

use App\Http\Controllers\crm\CompanyController;
use App\Http\Controllers\crm\MenuController;
use App\Http\Controllers\email\EmailController;
use App\Http\Controllers\formulario\CampoController;
use App\Http\Controllers\formulario\EmpresaController;
use App\Http\Controllers\formulario\FormController;
use App\Http\Controllers\formulario\FormSeccionController;
use App\Http\Controllers\formulario\SolicitudesCreditosWebController;
use App\Http\Controllers\proylecma\CreditoProylecmaController;
use App\Http\Controllers\proylecma\SolicitudesCreditosWebProylecmaController;
use App\Http\Controllers\rrhh\TrabajaConNostrosController;
use App\Http\Controllers\JWTController;
use App\Http\Controllers\paginaWeb\CreditoController;
use App\Http\Controllers\paginaWeb\PreciosController;
use App\Http\Controllers\ParametrosController;
use App\Http\Controllers\User\DepartamentoController;
use App\Http\Controllers\User\PerfilAnalistasController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\ProfileUserController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['middleware' => 'api'], function ($router) {
    Route::post('/register', [JWTController::class, 'register']);
    Route::post('/login', [JWTController::class, 'login']);
    Route::post('/profile', [JWTController::class, 'profile']);
});


Route::group(['middleware' => 'api', 'prefix' => 'users'], function ($router) {
    Route::post('/profile-user', [ProfileUserController::class, 'profile_user']);
    Route::put('/profile', [ProfileUserController::class, 'profile_user']);
});


Route::group(["prefix" => "ecommerce"], function ($router) {
    Route::get('/alm_precios', [PreciosController::class, 'alm_precios']);
    Route::post('/buscarCliente', [CreditoController::class, 'buscarCliente']);
    Route::post('/crearCaso', [CreditoController::class, 'crearCaso']);
    Route::get('/autorizarTratamientoDatos/{empresa}/{id}', [SolicitudesCreditosWebController::class, 'autorizarTratamientoDatos']);
});

Route::group(["prefix" => "proylecma"], function ($router) {
    Route::post('/buscarClienteProylecma', [CreditoProylecmaController::class, 'buscarClienteProylecma']);
    Route::post('/addSolicitudCreditoWebProylecma', [SolicitudesCreditosWebProylecmaController::class, 'addSolicitudCreditoWebProylecma']);
    Route::get('/autorizarTratamientoDatosProylecma/{id}', [SolicitudesCreditosWebProylecmaController::class, 'autorizarTratamientoDatosProylecma']);
    Route::get('/listAllEmpresasProylecma', [SolicitudesCreditosWebProylecmaController::class, 'listAllEmpresasProylecma']);
});

// TRABAJA CON NOSOTROS // RECURSOS HUMANOS
Route::group(["prefix" => "rrhh"], function ($router) {
    Route::get('/personaByIdentificacion/{identificacion}', [TrabajaConNostrosController::class, 'personaByIdentificacion']);
    Route::post('/addDatosPersonales', [TrabajaConNostrosController::class, 'addDatosPersonales']);
    Route::post('/addReferencia', [TrabajaConNostrosController::class, 'addReferencia']);
    Route::post('/editReferencia/{id}', [TrabajaConNostrosController::class, 'editReferencia']);
    Route::delete('/deleteReferencia/{id}', [TrabajaConNostrosController::class, 'deleteReferencia']);
    Route::post('/addExperienciaLaboral', [TrabajaConNostrosController::class, 'addExperienciaLaboral']);
    Route::post('/editExperienciaLaboral/{id}', [TrabajaConNostrosController::class, 'editExperienciaLaboral']);
    Route::delete('/deleteExperienciaLaboral/{id}', [TrabajaConNostrosController::class, 'deleteExperienciaLaboral']);
    Route::post('/addCurriculum/{id}', [TrabajaConNostrosController::class, 'addCurriculum']);
    Route::post('/addPostulacion', [TrabajaConNostrosController::class, 'addPostulacion']);

    Route::get('/listPaises', [TrabajaConNostrosController::class, 'listPaises']);
    Route::get('/listProvincias/{id_pais}', [TrabajaConNostrosController::class, 'listProvincias']);
    Route::get('/listTiposTelefonos', [TrabajaConNostrosController::class, 'listTiposTelefonos']);
    Route::get('/listCiudades', [TrabajaConNostrosController::class, 'listCiudades']);
    Route::get('/listCargos', [TrabajaConNostrosController::class, 'listCargos']);
    Route::get('/listParentescos', [TrabajaConNostrosController::class, 'listParentescos']);
    // Route::post('/addFormularioTrabajaConNosotros', [TrabajaConNostrosController::class, 'addFormularioTrabajaConNosotros']); // este ocupaba antes cuando era un formulario simple de trabaja con nosotros (actualmente ya no ocupo)
});


Route::group(["prefix" => "crm"], function ($router) {

    // DEPARTAMENTO

    Route::get('/allDepartamento', [DepartamentoController::class, 'allDepartamento']); // listar
    Route::get('/listDepAllUser', [DepartamentoController::class, 'listAllUser']); // listar
    Route::get('/listDepartamento', [DepartamentoController::class, 'listDepartamento']); // listar
    Route::post('/addDepartamento', [DepartamentoController::class, 'addDepartamento']); // guardar
    Route::post('/editDepartamento/{id}', [DepartamentoController::class, 'editDepartamento']); // Editar
    Route::delete('/deleteDepartamento/{id}', [DepartamentoController::class, 'deleteDepartamento']); // Eliminar

    // USUARIOS

    Route::get('allUsers', [UserController::class, 'allUsers']); // by caso_id
    Route::post('/addUser', [UserController::class, 'addUser']); // guardar
    Route::post('/editUser/{id}', [UserController::class, 'editUser']); // Editar
    Route::delete('/deleteUser/{id}', [UserController::class, 'deleteUser']); // Eliminar
    Route::get('/listUsuariosByTableroId/{tablero_id}', [UserController::class, 'listUsuariosByTableroId']); // listar usuarios del tablero
    Route::get('/listUsuarioById/{user_id}', [UserController::class, 'listUsuarioById']); // listar usuario por ID

    Route::get('/listAlmacenes', [UserController::class, 'listAlmacenes']); // listar almacenes

    Route::post('/editEnLineaUser/{user_id}', [UserController::class, 'editEnLineaUser']); // editar en linea del usuario

    // Perfil Analistas

    Route::get('/listAllPerfilAnalistas', [PerfilAnalistasController::class, 'listAllPerfilAnalistas']);
    Route::post('/addPerfilAnalistas', [PerfilAnalistasController::class, 'addPerfilAnalistas']); // guardar
    Route::post('/editPerfilAnalistas/{id}', [PerfilAnalistasController::class, 'editPerfilAnalistas']); // Editar
    Route::delete('/deletePerfilAnalistas/{id}', [PerfilAnalistasController::class, 'deletePerfilAnalistas']); // Eliminar

    // EMPRESA
    Route::get('/listAllEmpresas', [EmpresaController::class, 'listAllEmpresas']);
    Route::get('/listEmpresas', [EmpresaController::class, 'listEmpresas']);
    Route::post('/addEmpresa', [EmpresaController::class, 'addEmpresa']);
    Route::post('/editEmpresa/{id}', [EmpresaController::class, 'editEmpresa']);
    Route::delete('/deleteEmpresa/{id}', [EmpresaController::class, 'deleteEmpresa']);

    // Enviar correo de prueba a juanjgsj@gmail.com
    Route::get('/sendEmailPruebaAlmacenesEspana', [EmailController::class, 'sendEmailPruebaAlmacenesEspana']); // Almacenes españa
    Route::get('/sendEmailPruebaProylecma', [EmailController::class, 'sendEmailPruebaProylecma']); // Proylecma

});


// ACCESOS

Route::group(['prefix' => 'profile'], function () {
    Route::get('all', [ProfileController::class, 'all']);
    Route::get('list', [ProfileController::class, 'list']);
    Route::get('list/{id}', [ProfileController::class, 'findById']);
    Route::post('create', [ProfileController::class, 'create']);
    Route::post('edit/{id}', [ProfileController::class, 'edit']);
    Route::delete('deleteProfile/{id}', [ProfileController::class, 'deleteProfile']);
    Route::post('clonProfile', [ProfileController::class, 'clonProfile']);
    Route::get('buscarAccesosByProfileId/{id}', [ProfileController::class, 'buscarAccesosByProfileId']);
});


Route::group(['prefix' => 'access'], function () {
    Route::get('program/{profile}/{program}', [ProfileController::class, 'findByProgram']);
    Route::get('menu/{userid}', [ProfileController::class, 'findByUser']);
});


Route::group(['prefix' => 'company'], function () {
    Route::get('lista/{id}', [CompanyController::class, 'findById']);
    Route::put('editar/{id}', [CompanyController::class, 'edit']);
});


Route::group(['prefix' => 'menu'], function () {
    Route::get('list', [MenuController::class, 'list']);
    Route::get('list/{id}', [MenuController::class, 'findById']);

    Route::post('addMenu', [MenuController::class, 'addMenu']);
    Route::post('editMenu/{id}', [MenuController::class, 'editMenu']);
    Route::delete('deleteMenu/{id}', [MenuController::class, 'deleteMenu']);
});


// FORMULARIO DINAMICO

Route::group(["prefix" => "form"], function ($router) {
    Route::get('/list', [FormController::class, 'list']);
    Route::get('/storeA/{formId}', [FormController::class, 'storeA']);
    Route::get('/storeB/{formId}/{userId}', [FormController::class, 'storeB']);
    Route::get('/cargarFormulario/{formId}', [FormController::class, 'cargarFormulario']);
    Route::get('/listByDepar/{depId}/{userId}', [FormController::class, 'listByDepar']); //
    Route::get('/formUser/{depId}/{userId}', [FormController::class, 'formUser']); //formUser
    Route::get('/byId/{formId}', [FormController::class, 'byId']); //formUser
    Route::get('/listAll', [FormController::class, 'listAll']); //
    Route::get('/listAnonimos', [FormController::class, 'listAnonimos']);
    Route::get('/impresion/{formId}/{userId}', [FormController::class, 'impresion']); //impresion
    Route::put('/edit/{id}', [FormController::class, 'edit']); //
    Route::post('/addFormulario', [FormController::class, 'addFormulario']); //
    Route::get('listFormByIdTablero/{tab_id}', [FormController::class, 'listFormByIdTablero']); //
    Route::get('/storeCasoForm/{casoId}', [FormController::class, 'storeCasoForm']);
});

Route::group(['prefix' => 'form/campo'], function ($router) {
    Route::get('/store', [CampoController::class, 'store']);
    Route::get('/full/{id}', [CampoController::class, 'full']);
    Route::get('/list', [CampoController::class, 'list']);
    Route::get('/listAll', [CampoController::class, 'listAll']);
    Route::get('/byId/{id}', [CampoController::class, 'byId']);
    Route::get('/deleted', [CampoController::class, 'deleted']);
    Route::get('/restoreById/{id}', [CampoController::class, 'restoreById']);
    Route::put('/edit/{id}', [CampoController::class, 'edit']);
    Route::delete('/deleteById/{id}', [CampoController::class, 'deleteById']);
    Route::post('/add', [CampoController::class, 'add']); //addCampoValor
    Route::post('/addCampoValor', [CampoController::class, 'addCampoValor']); //addCampoValor
});

Route::group(['prefix' => 'form/seccion'], function ($router) {
    Route::get('/store', [FormSeccionController::class, 'store']);
    Route::post('/add', [FormSeccionController::class, 'add']);
    Route::put('/edit/{id}', [FormSeccionController::class, 'edit']);
});

//----------------------- PARAMETROS DIRECCION ----------------------------------------------
Route::group(['prefix' => 'parametros'], function ($router) {
    Route::get('/direccion', [ParametrosController::class, 'direccion']); //
    Route::get('/direccionParroquias/{cantonId}', [ParametrosController::class, 'direccionParroquias']); //direccionParroquias
});
