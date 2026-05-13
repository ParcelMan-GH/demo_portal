<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\DeliveryRun;

$user = Admin::query()->first();
if (!$user) {
    throw new RuntimeException('No admin user');
}

$request = Request::create('/admin/delivery-runs/2', 'GET', [], [], [], ['HTTP_HOST'=>'parcelman.test']);
$app->instance('request', $request);
$request->setJson(null);
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
$view = view('admin.delivery-runs.show', compact('run', 'statusLabel'))->render();

echo "has function tag: ", (strpos($view, 'window.adminDeliveryRunPage') !== false ? 'yes' : 'no'), "\n";
echo "x-data present: ", (strpos($view, 'x-data=\"adminDeliveryRunPage()\"') !== false ? 'yes' : 'no'), "\n";

if (strpos($view, 'window.adminDeliveryRunPage') !== false) {
    $i = strpos($view, 'window.adminDeliveryRunPage');
    echo substr($view, max(0, $i - 240), 700), "\n";
}

file_put_contents('/tmp/run_show.html', $view);
echo "saved /tmp/run_show.html\n";
?>
