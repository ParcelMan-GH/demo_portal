<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\DeliveryRun;

$user = User::query()->first();
if (!$user) {
    throw new RuntimeException('No user');
}

$request = Request::create('/admin/delivery-runs/2', 'GET', [], [], [], ['HTTP_HOST' => 'parcelman.test']);
$request->setJson(null);
$request->server->set('REMOTE_ADDR', '127.0.0.1');
$request->server->set('SERVER_NAME', 'parcelman.test');
$app->instance('request', $request);

Auth::guard('admin')->setUser($user);

$run = DeliveryRun::with([
    'warehouse','stops','items','assignedDriver','sortBatch','createdBy','stops.region','stops.district','stops.confirmedBy',
    'stops.items.shipmentItem.shipment',
    'stops.items.shipmentItem.images',
    'stops.items.shipmentItem.warehouseReceiptItems.photos',
    'stops.verificationAttempts',
])->find(2);
if (!$run) {
    throw new RuntimeException('No run id 2');
}

$statusLabel = 'Draft';
$view = view('admin.delivery-runs.show', compact('run','statusLabel'))->render();

echo "has function tag: ", (strpos($view, 'window.adminDeliveryRunPage') !== false ? 'yes' : 'no'), "\n";
echo "x-data present: ", (strpos($view, 'x-data=\"adminDeliveryRunPage()\"') !== false ? 'yes' : 'no'), "\n";
if (strpos($view, 'window.adminDeliveryRunPage') !== false) {
    $i = strpos($view, 'window.adminDeliveryRunPage');
    echo substr($view, max(0, $i - 300), 900), "\n";
}
echo "html len ", strlen($view), "\n";
file_put_contents('/tmp/run_show.html', $view);
?>
