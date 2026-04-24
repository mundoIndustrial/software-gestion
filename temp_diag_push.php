<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'vapid_public=' . (config('push.vapid.public_key') ? 'set' : 'empty') . PHP_EOL;
echo 'vapid_private=' . (config('push.vapid.private_key') ? 'set' : 'empty') . PHP_EOL;
echo 'vapid_subject=' . (config('push.vapid.subject') ? 'set' : 'empty') . PHP_EOL;

$cartera = App\Models\User::where('email','carteramundo@gmail.com')->first();
if(!$cartera){ echo "user_not_found\n"; exit; }

echo 'cartera_user_id=' . $cartera->id . PHP_EOL;
$subs = App\Models\PushSubscription::where('user_id',$cartera->id)->get(['id','endpoint','content_encoding','last_seen_at','created_at']);
echo 'subs_count=' . $subs->count() . PHP_EOL;
foreach($subs as $s){
  echo $s->id . '|enc=' . $s->content_encoding . '|last=' . $s->last_seen_at . '|created=' . $s->created_at . PHP_EOL;
}
