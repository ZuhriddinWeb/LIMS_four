<?php

use App\Http\Controllers\api\CalculatorController;
use App\Http\Controllers\api\DocumentNumberPageController;
use App\Http\Controllers\api\DocumentsController;
use App\Http\Controllers\api\FormulaController;
use App\Http\Controllers\api\GroupsController;
use App\Http\Controllers\api\PeriodTypeController;
use App\Http\Controllers\api\ServersController;

use App\Http\Controllers\api\SheetController;
use App\Http\Controllers\api\SheetFormulasController;
use App\Http\Controllers\api\StaticParametersController;

use App\Http\Controllers\api\SvodkaFormulaController;
use App\Http\Controllers\api\TermController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\UnitsController;
use App\Http\Controllers\api\GraphicTimesController;
use App\Http\Controllers\api\SourcesController;
use App\Http\Controllers\api\ChangesController;
use App\Http\Controllers\api\GraphicsController;
use App\Http\Controllers\api\ParamTypesController;
use App\Http\Controllers\api\ParamsController;
use App\Http\Controllers\api\FactoryController;
use App\Http\Controllers\api\FactoryStructureController;
use App\Http\Controllers\api\ParametrValueController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\api\ParamsGraphController;
use App\Http\Controllers\api\BlogsController;
use App\Http\Controllers\api\NumberPageController;

use App\Http\Controllers\TreeController;

// Лабораторный блок (ЛИМС) — справочники
use App\Http\Controllers\api\LabLaboratoriesController;
use App\Http\Controllers\api\LabGroupsController;
use App\Http\Controllers\api\LabSamplePointsController;
use App\Http\Controllers\api\LabSampleTypesController;
use App\Http\Controllers\api\LabAnalytesController;
use App\Http\Controllers\api\LabMethodsController;
use App\Http\Controllers\api\LabInstrumentsController;
use App\Http\Controllers\api\LabDepartmentsController;
use App\Http\Controllers\api\LabSamplesController;
use App\Http\Controllers\api\LabJournalController;
use App\Http\Controllers\api\LabProductsController;
use App\Http\Controllers\api\LabCertificatesController;
use App\Http\Controllers\api\LabStandardsController;
use App\Http\Controllers\api\LabQcController;
use App\Http\Controllers\api\LabEquipmentController;
use App\Http\Controllers\api\LabAiController;
use App\Http\Controllers\api\LabDashboardController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('/login', [UserController::class, 'login']);
Route::middleware('auth:sanctum')->get('/logout', [UserController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'authenticatedUser']);

Route::match(['get', 'post', 'put', 'delete'], '/units/{id?}', [UnitsController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/pages/{id?}', [NumberPageController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/addfordoc/{id?}', [DocumentNumberPageController::class, 'handle']);

Route::match(['get', 'post', 'put', 'delete'], '/graphictimes/{id?}', [GraphicTimesController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/graphicterms/{id?}', [TermController::class, 'handle']);


Route::match(['get', 'post', 'put', 'delete'], '/sources/{id?}', [SourcesController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/changes/{id?}', [ChangesController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/graphics/{id?}', [GraphicsController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/paramtypes/{id?}', [ParamTypesController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/param/{id?}', [ParamsController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/source/{id?}', [SourcesController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/factory/{id?}', [FactoryController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/structure/{id?}', [FactoryStructureController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/vparams/{id?}', [ParametrValueController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/users/{id?}', [UserController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/role/{id?}', [RoleController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/user_role/{id?}', [UserRoleController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/paramsgraph/{id?}', [ParamsGraphController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/blogs/{id?}', [BlogsController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/formula/{id?}', [FormulaController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/calculator/{id?}', [CalculatorController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/documents/{id?}', [DocumentsController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/servers/{id?}', [ServersController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/groups/{id?}', [GroupsController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/static/{id?}', [StaticParametersController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/svodkaFormula/{id?}', [SvodkaFormulaController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/periodType/{id?}', [PeriodTypeController::class, 'handle']);

// Скачивание файлов оставляем открытым: window.open() не отправляет заголовок
// Authorization, иначе скачивание бы ломалось под auth-middleware.
Route::get('/lab/equipment-file/{fileId}/download', [LabEquipmentController::class, 'downloadFile']);
Route::get('/lab/sample-file/{fileId}/download', [LabSamplesController::class, 'downloadFile']);

// === Все остальные маршруты лабораторного блока требуют авторизации (Sanctum) ===
Route::middleware('auth:sanctum')->group(function () {

// Лабораторный блок — справочники (Фаза Л1). Действие определяется по HTTP-методу.
Route::match(['get', 'post', 'put', 'delete'], '/lab/laboratories/{id?}', [LabLaboratoriesController::class, 'handle'])->middleware('perm:menu.lab_laboratories');
Route::match(['get', 'post', 'put', 'delete'], '/lab/groups/{id?}', [LabGroupsController::class, 'handle'])->middleware('perm:menu.lab_groups');
Route::match(['get', 'post', 'put', 'delete'], '/lab/points/{id?}', [LabSamplePointsController::class, 'handle'])->middleware('perm:menu.lab_points');
Route::match(['get', 'post', 'put', 'delete'], '/lab/types/{id?}', [LabSampleTypesController::class, 'handle'])->middleware('perm:menu.lab_types');
Route::match(['get', 'post', 'put', 'delete'], '/lab/analytes/{id?}', [LabAnalytesController::class, 'handle'])->middleware('perm:menu.lab_analytes');
Route::match(['get', 'post', 'put', 'delete'], '/lab/methods/{id?}', [LabMethodsController::class, 'handle'])->middleware('perm:menu.lab_methods');
Route::match(['get', 'post', 'put', 'delete'], '/lab/instruments/{id?}', [LabInstrumentsController::class, 'handle'])->middleware('perm:menu.lab_instruments');
Route::match(['get', 'post', 'put', 'delete'], '/lab/departments/{id?}', [LabDepartmentsController::class, 'handle'])->middleware('perm:menu.lab_departments');

// Лабораторный блок — пробы (Фаза Л2). Ввод/проверка/утверждение результата = update.
Route::post('/lab/samples/register', [LabSamplesController::class, 'register'])->middleware('perm:menu.lab_samples,create');
Route::get('/lab/samples', [LabSamplesController::class, 'index'])->middleware('perm:menu.lab_samples,view');
Route::get('/lab/samples/{id}', [LabSamplesController::class, 'show'])->middleware('perm:menu.lab_samples,view');
Route::get('/lab/samples/{id}/history', [LabSamplesController::class, 'history'])->middleware('perm:menu.lab_samples,view');
Route::put('/lab/samples/{id}/status', [LabSamplesController::class, 'updateStatus'])->middleware('perm:menu.lab_samples,update');
Route::delete('/lab/samples/{id}', [LabSamplesController::class, 'destroy'])->middleware('perm:menu.lab_samples,delete');
Route::post('/lab/determinations/{id}/result', [LabSamplesController::class, 'saveResult'])->middleware('perm:menu.lab_samples,update');
Route::post('/lab/determinations/{id}/review', [LabSamplesController::class, 'review'])->middleware('perm:menu.lab_samples,update');
Route::post('/lab/determinations/{id}/approve', [LabSamplesController::class, 'approve'])->middleware('perm:menu.lab_samples,update');
Route::post('/lab/determinations/{id}/reopen', [LabSamplesController::class, 'reopen'])->middleware('perm:menu.lab_samples,update');
Route::post('/lab/samples/{id}/dispose', [LabSamplesController::class, 'dispose'])->middleware('perm:menu.lab_samples,update');
Route::post('/lab/samples/{id}/file', [LabSamplesController::class, 'uploadFile'])->middleware('perm:menu.lab_samples,update');
Route::delete('/lab/sample-file/{fileId}', [LabSamplesController::class, 'deleteFile'])->middleware('perm:menu.lab_samples,delete');

// Справочник мест хранения проб (ТЗ 2.11)
Route::match(['get', 'post', 'put', 'delete'], '/lab/storage-locations/{id?}', [\App\Http\Controllers\api\LabStorageLocationsController::class, 'handle'])->middleware('perm:menu.lab_storage');

// Лабораторный блок — журнал КХА / отчёты (Фаза Л3)
Route::get('/lab/journal', [LabJournalController::class, 'journal'])->middleware('perm:menu.lab_journal,view');

// Лабораторный блок — паспорта качества (Фаза Л4)
Route::match(['get', 'post', 'put', 'delete'], '/lab/products/{id?}', [LabProductsController::class, 'handle'])->middleware('perm:menu.lab_products');
Route::get('/lab/certificates', [LabCertificatesController::class, 'index'])->middleware('perm:menu.lab_certificates,view');
Route::post('/lab/certificates/register', [LabCertificatesController::class, 'register'])->middleware('perm:menu.lab_certificates,create');
Route::get('/lab/certificates/from-sample/{sampleId}', [LabCertificatesController::class, 'fromSample'])->middleware('perm:menu.lab_certificates,view');
Route::get('/lab/certificates/{id}', [LabCertificatesController::class, 'show'])->middleware('perm:menu.lab_certificates,view');
Route::put('/lab/certificates/{id}', [LabCertificatesController::class, 'update'])->middleware('perm:menu.lab_certificates,update');
Route::delete('/lab/certificates/{id}', [LabCertificatesController::class, 'destroy'])->middleware('perm:menu.lab_certificates,delete');

// Лабораторный блок — внутрилаб. контроль ВЛК (Фаза Л5)
Route::get('/lab/standards', [LabStandardsController::class, 'index'])->middleware('perm:menu.lab_standards,view');
Route::post('/lab/standards/register', [LabStandardsController::class, 'register'])->middleware('perm:menu.lab_standards,create');
Route::get('/lab/standards/{id}/values', [LabStandardsController::class, 'values'])->middleware('perm:menu.lab_standards,view');
Route::get('/lab/standards/{id}', [LabStandardsController::class, 'show'])->middleware('perm:menu.lab_standards,view');
Route::put('/lab/standards/{id}', [LabStandardsController::class, 'update'])->middleware('perm:menu.lab_standards,update');
Route::delete('/lab/standards/{id}', [LabStandardsController::class, 'destroy'])->middleware('perm:menu.lab_standards,delete');

Route::get('/lab/qc', [LabQcController::class, 'index'])->middleware('perm:menu.lab_qc,view');
Route::post('/lab/qc/register', [LabQcController::class, 'register'])->middleware('perm:menu.lab_qc,create');
Route::get('/lab/qc/chart', [LabQcController::class, 'chart'])->middleware('perm:menu.lab_qc,view');
Route::delete('/lab/qc/{id}', [LabQcController::class, 'destroy'])->middleware('perm:menu.lab_qc,delete');

// Лабораторный блок — управление оборудованием (роль menu.lab_instruments)
Route::get('/lab/equipment', [LabEquipmentController::class, 'index'])->middleware('perm:menu.lab_instruments,view');
Route::post('/lab/equipment/register', [LabEquipmentController::class, 'register'])->middleware('perm:menu.lab_instruments,create');
Route::get('/lab/equipment/{id}', [LabEquipmentController::class, 'show'])->middleware('perm:menu.lab_instruments,view');
Route::put('/lab/equipment/{id}/status', [LabEquipmentController::class, 'updateStatus'])->middleware('perm:menu.lab_instruments,update');
Route::post('/lab/equipment/{id}/event', [LabEquipmentController::class, 'addEvent'])->middleware('perm:menu.lab_instruments,update');
Route::post('/lab/equipment/{id}/file', [LabEquipmentController::class, 'uploadFile'])->middleware('perm:menu.lab_instruments,update');
Route::put('/lab/equipment/{id}', [LabEquipmentController::class, 'update'])->middleware('perm:menu.lab_instruments,update');
Route::delete('/lab/equipment/{id}', [LabEquipmentController::class, 'destroy'])->middleware('perm:menu.lab_instruments,delete');
Route::delete('/lab/equipment-file/{fileId}', [LabEquipmentController::class, 'deleteFile'])->middleware('perm:menu.lab_instruments,delete');

// Лабораторный блок — ИИ-помощник (тренды вода/воздух)
Route::get('/lab/ai/trend', [LabAiController::class, 'trend']);

// Лабораторный блок — дашборд
Route::get('/lab/dashboard', [LabDashboardController::class, 'summary']);

// Лабораторный блок — центр оповещений
Route::get('/lab/alerts', [\App\Http\Controllers\api\LabAlertsController::class, 'index']);

// Лабораторный блок — технологический контроль (сменные журналы)
Route::get('/lab/process', [\App\Http\Controllers\api\LabProcessController::class, 'index'])->middleware('perm:menu.lab_process,view');
Route::get('/lab/process/journal', [\App\Http\Controllers\api\LabProcessController::class, 'journal'])->middleware('perm:menu.lab_process,view');
Route::post('/lab/process/bulk', [\App\Http\Controllers\api\LabProcessController::class, 'bulkSave'])->middleware('perm:menu.lab_process,update');
Route::delete('/lab/process/{id}', [\App\Http\Controllers\api\LabProcessController::class, 'destroy'])->middleware('perm:menu.lab_process,delete');

// Лабораторный блок — журнал аудита (ISO 17025 / ALCOA+)
Route::get('/lab/audit', [\App\Http\Controllers\api\LabAuditController::class, 'index']);
Route::get('/lab/audit/meta', [\App\Http\Controllers\api\LabAuditController::class, 'meta']);
Route::get('/lab/audit/{entityType}/{entityId}', [\App\Http\Controllers\api\LabAuditController::class, 'forEntity']);

}); // === конец группы auth:sanctum лабораторного блока ===

Route::get('/static', [StaticParametersController::class, 'index']); // sendagi bor
Route::get('/static-with-numberpage/{id}', [StaticParametersController::class, 'staticWithNumberPage']); // sendagi bor


// Formulalar
Route::get('/sheet/formula', [SheetController::class, 'getFormulas']);
Route::post('/sheet/formula', [SheetController::class, 'saveFormula']);

// Kross-sahifa qiymat (B6 kabi)
Route::get('/sheet/value', [SheetController::class, 'getValue']);

// (ixtiyoriy) katak qiymatini qo'lda saqlash/yangilash
Route::post('/sheet/value', [SheetController::class, 'saveValue']);
Route::post('/sheet/values/bulk', [SheetController::class, 'saveValuesBulk']);

Route::get('/svodkaFormulaEdit/{param_id}', [SvodkaFormulaController::class, 'getByParam']);



Route::get('/structures-number', [DocumentNumberPageController::class, 'getStructures']);
Route::get('/pages-number/{sexId}', [DocumentNumberPageController::class, 'getPages']);

Route::get('/groups-number/{sexId}/{pageId}', [DocumentNumberPageController::class, 'getGroups']);
Route::get('/parameters-number/{sexId}/{pageId}/{groupId}', [DocumentNumberPageController::class, 'getParameters']);
// Route::get('/periodType', [StaticParametersController::class, 'periodType']);


Route::get('/getUserData/{id}', [DocumentsController::class, 'getUserData']);
Route::get('/document/{id}/{start}', [DocumentsController::class, 'generate']);
Route::get('/structures/{id}', [FactoryStructureController::class, 'getForUser']);
Route::get('/structureTree', [FactoryStructureController::class, 'tree']);
Route::get('/addfordoc/selected/{doc_id}', [DocumentNumberPageController::class, 'selected']);
Route::get('/addfordocSelect/{doc_id}', [DocumentNumberPageController::class, 'selectTree']);

Route::get('/doc/tree/{id}', [DocumentNumberPageController::class, 'treeFlat']);   // ↑ rows
Route::get('/doc/selected/{id}', [DocumentNumberPageController::class, 'selected']);   // GUID[]
Route::post('/doc/save', [DocumentNumberPageController::class, 'save']);       // saqlash


Route::get('/tree', [TreeController::class, 'getTree']);
Route::post('/node-clicked', [TreeController::class, 'handleNodeClick']);
Route::post('/treeChart', [TreeController::class, 'treeChart']);

Route::get('get-params-for-user-count/{user_id}/{change_id}', [ParamsGraphController::class, 'getParamsForUserCount']);
Route::get('get-params-for-id-edit/{param_id}', [ParamsGraphController::class, 'getRowParamEdit']);
Route::get('get-params-for-user/{user_id}/{change_id}/{date}/{tabId}', [ParamsGraphController::class, 'getParamsForUser']);
Route::get('get-params-for-user-horizontal/{user_id}/{change_id}/{date}/{tabId}', [ParamsGraphController::class, 'getParamsForUserHorizontal']);

Route::get('get-params-for-id/{param_id}', [ParametrValueController::class, 'getParamsForId']);
Route::post('vparamsEdit', [ParametrValueController::class, 'update']);
Route::get('vparams-value/{factoryId}/{cuurent_date}/{currnetchange}', [ParametrValueController::class, 'getByBlog']);
Route::get('selectResultBlogs/{doc_id}/{date}', [ParametrValueController::class, 'selectResultBlogs']);
Route::get('calculator-structure/{id}', [DocumentNumberPageController::class, 'getCalculator']);

Route::get('restart-password/{user_id}', [UserController::class, 'restart']);
Route::get('/broadcast-time', [ParametrValueController::class, 'sendTimeUpdate']);
Route::get('/vparamsGetValue/{id}', [ParametrValueController::class, 'vparamsGetValue']);
Route::get('/getRowPageResult/{id}', [ParamsGraphController::class, 'getRowPageResult']);

// Route::get('/vparamsuser/{blog_id}/{change_id}/{date}', [ParametrValueController::class, 'getByBlog']);
Route::get('/paramWithId/{id}', [ParamsGraphController::class, 'getRowParamID']);
Route::get('/pages-select/{id}', [NumberPageController::class, 'getRowPages']);
Route::get('/getRowPage/{id}', [NumberPageController::class, 'getRowPage']);
Route::get('/pages-svodka/{id}', [NumberPageController::class, 'getSvodka']);

Route::get('/getRowGroup/{idS}/{idP}', [GroupsController::class, 'getRowGroup']);
Route::get('/getRowGroupEdit/{id}', [GroupsController::class, 'getRowGroupEdit']);
Route::get('/getRowGroupEdits/{id}', [GroupsController::class, 'getRowGroupEdits']);

Route::get('/getRowGroupWithId/{id}', [GroupsController::class, 'getRowGroupWithId']);
Route::get('/getRowGroupWith/{id}', [GroupsController::class, 'getRowGroupWith']);
Route::post('/static-params/upsert', [StaticParametersController::class, 'upsert']);
Route::get('/staticCard/{id}', [StaticParametersController::class, 'staticCard']);




Route::get('/get-graph-with-params/{id}', [ParamsGraphController::class, 'getGraficWithParams']);
Route::get('/getForFormule/{id}', [ParamsGraphController::class, 'getForFormule']);
Route::get('/getRowTimes/{id}/{GParamID}/{GPid}/{GrapicsID}', [GraphicTimesController::class, 'getRowTimes']);
Route::get('/getTimes/{id}', [GraphicTimesController::class, 'getTimes']);
Route::get('/time/{id}', [GraphicTimesController::class, 'time']);


Route::get('/getForFormuleTimes/{GParamID}', [GraphicTimesController::class, 'getRowFormuleTimes']);
Route::get('/withCardId/{id}/{pageId}', [ParamsGraphController::class, 'withCardId']);
Route::get('/getRowBlog/{id}', [BlogsController::class, 'getRowBlog']);
Route::get('/getForFormuleId/{paramID}/{timeID}', [CalculatorController::class, 'getForFormuleId']);


