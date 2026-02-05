<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $subscription = $user->activeSubscription()->with('plan')->first() 
            ?? $user->latestSubscription()->with('plan')->first();

        // Get campaign analytics data for the last 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        $campaigns = Campaign::where('user_id', $user->id)->get();
        
        // Daily message stats for the chart (last 14 days)
        $dailyStats = CampaignMessage::join('campaigns', 'campaign_messages.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->where('campaign_messages.created_at', '>=', Carbon::now()->subDays(14))
            ->select(
                DB::raw('DATE(campaign_messages.created_at) as date'),
                DB::raw("COUNT(CASE WHEN campaign_messages.status IN ('sent', 'delivered', 'read') THEN 1 END) as sent"),
                DB::raw("COUNT(CASE WHEN campaign_messages.delivered_at IS NOT NULL THEN 1 END) as delivered"),
                DB::raw("COUNT(CASE WHEN campaign_messages.read_at IS NOT NULL THEN 1 END) as opened"),
                DB::raw("COUNT(CASE WHEN campaign_messages.clicked_at IS NOT NULL THEN 1 END) as clicked"),
                DB::raw("COUNT(CASE WHEN campaign_messages.status = 'failed' THEN 1 END) as failed")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing dates
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayData = $dailyStats->firstWhere('date', $date);
            $chartData[] = [
                'date' => Carbon::parse($date)->format('M d'),
                'sent' => $dayData->sent ?? 0,
                'delivered' => $dayData->delivered ?? 0,
                'opened' => $dayData->opened ?? 0,
                'clicked' => $dayData->clicked ?? 0,
                'failed' => $dayData->failed ?? 0,
            ];
        }

        // Overall stats
        $totalMessages = CampaignMessage::join('campaigns', 'campaign_messages.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->count();
        
        $sentMessages = CampaignMessage::join('campaigns', 'campaign_messages.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->whereIn('campaign_messages.status', ['sent', 'delivered', 'read'])
            ->count();

        $deliveredMessages = CampaignMessage::join('campaigns', 'campaign_messages.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->whereNotNull('campaign_messages.delivered_at')
            ->count();

        $openedMessages = CampaignMessage::join('campaigns', 'campaign_messages.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->whereNotNull('campaign_messages.read_at')
            ->count();

        $clickedMessages = CampaignMessage::join('campaigns', 'campaign_messages.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->whereNotNull('campaign_messages.clicked_at')
            ->count();

        $overallStats = [
            'total_campaigns' => $campaigns->count(),
            'total_messages' => $totalMessages,
            'sent' => $sentMessages,
            'delivered' => $deliveredMessages,
            'opened' => $openedMessages,
            'clicked' => $clickedMessages,
            'send_rate' => $totalMessages > 0 ? round(($sentMessages / $totalMessages) * 100, 1) : 0,
            'delivery_rate' => $sentMessages > 0 ? round(($deliveredMessages / $sentMessages) * 100, 1) : 0,
            'open_rate' => $deliveredMessages > 0 ? round(($openedMessages / $deliveredMessages) * 100, 1) : 0,
            'click_rate' => $openedMessages > 0 ? round(($clickedMessages / $openedMessages) * 100, 1) : 0,
        ];

        // Messages sent this month (current calendar month)
        $messagesThisMonth = CampaignMessage::join('campaigns', 'campaign_messages.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->whereYear('campaign_messages.created_at', Carbon::now()->year)
            ->whereMonth('campaign_messages.created_at', Carbon::now()->month)
            ->whereIn('campaign_messages.status', ['sent', 'delivered', 'read'])
            ->count();

        // Recent campaigns with stats
        $recentCampaigns = Campaign::where('user_id', $user->id)
            ->withCount([
                'messages as total_count',
                'messages as success_count' => function ($query) {
                    $query->whereIn('status', ['sent', 'delivered', 'read']);
                },
                'messages as opened_count' => function ($query) {
                    $query->whereNotNull('read_at');
                },
                'messages as clicked_count' => function ($query) {
                    $query->whereNotNull('clicked_at');
                }
            ])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'subscription' => $subscription,
            'whatsappCount' => $user->whatsappNumbers()->count(),
            'contactCount' => $user->contacts()->count(),
            'messagesThisMonth' => $messagesThisMonth,
            'chartData' => $chartData,
            'overallStats' => $overallStats,
            'recentCampaigns' => $recentCampaigns,
        ]);
    }

    public function exportReport(Request $request)
    {
        $user = auth()->user();
        
        $campaignId = $request->input('campaign_id');
        $dateFrom = $request->input('from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('to', Carbon::now()->format('Y-m-d'));

        $query = CampaignMessage::join('campaigns', 'campaign_messages.campaign_id', '=', 'campaigns.id')
            ->join('contacts', 'campaign_messages.contact_id', '=', 'contacts.id')
            ->where('campaigns.user_id', $user->id)
            ->whereBetween('campaign_messages.created_at', [$dateFrom, $dateTo]);

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $messages = $query->select([
            'campaigns.name as campaign_name',
            'contacts.name as contact_name',
            'contacts.phone_number',
            'campaign_messages.status',
            'campaign_messages.sent_at',
            'campaign_messages.delivered_at',
            'campaign_messages.read_at',
            'campaign_messages.clicked_at',
            'campaign_messages.created_at',
        ])->get();

        // Generate CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="campaign_report_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($messages) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Campaign',
                'Contact Name',
                'Phone Number',
                'Status',
                'Sent At',
                'Delivered At',
                'Read At',
                'Clicked At',
                'Created At'
            ]);

            // Data rows
            foreach ($messages as $message) {
                fputcsv($file, [
                    $message->campaign_name,
                    $message->contact_name,
                    $message->phone_number,
                    $message->status,
                    $message->sent_at,
                    $message->delivered_at,
                    $message->read_at,
                    $message->clicked_at,
                    $message->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
