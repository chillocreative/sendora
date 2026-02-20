<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SubscriptionPlan;

return new class extends Migration
{
    public function up(): void
    {
        SubscriptionPlan::firstOrCreate(
            ['name' => 'Lifetime'],
            [
                'monthly_price' => 1999,
                'yearly_price'  => 0,
                'is_lifetime'   => true,
                'is_active'     => true,
                'limits'        => [
                    'whatsapp_nos' => 2,
                    'contacts'     => 2000,
                    'messages'     => 5000,
                    'features'     => [
                        'text_support'    => true,
                        'image_support'   => true,
                        'file_support'    => true,
                        'scheduling'      => true,
                        'pdf_support'     => true,
                        'link_preview'    => true,
                        'auto_reply'      => true,
                        'message_preview' => true,
                        'multi_user'      => false,
                        'webhooks'        => false,
                        'api_access'      => false,
                    ],
                ],
            ]
        );
    }

    public function down(): void
    {
        SubscriptionPlan::where('name', 'Lifetime')->delete();
    }
};
