<?php

namespace App\Filament\Traits;

use App\Models\Comment;
use App\Models\Contract;
use Carbon\Carbon;

/**
 * Trait HasUnifiedTimeline
 * 
 * Fornisce la logica per generare una timeline unificata di commenti e eventi
 * Usata da SupplierTimeline e GlobalTimeline
 */
trait HasUnifiedTimeline
{
    /**
     * Metodo astratto - deve essere implementato dalle classi che usano il trait
     * Ritorna la collection di entità da cui estrarre i commenti/eventi
     */
    abstract protected function getTimelineEntities();

    /**
     * Genera tutte le attività dalla timeline
     */
    protected function getTimelineActivities(): array
    {
        $activities = [];
        
        // Get all comments for the entities
        $comments = Comment::with(['subject', 'metadata', 'links'])
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();

        foreach ($comments as $comment) {
            $activities[] = $this->formatCommentActivity($comment);
        }

        // Get all contract starts
        $contractStarts = Contract::whereNotNull('start_date')
            ->with('supplier')
            ->orderBy('start_date', 'desc')
            ->limit(200)
            ->get();

        foreach ($contractStarts as $contract) {
            $activities[] = $this->formatContractStartActivity($contract);
        }

        // Get all contract ends
        $contractEnds = Contract::whereNotNull('end_date')
            ->with('supplier')
            ->orderBy('end_date', 'desc')
            ->limit(200)
            ->get();

        foreach ($contractEnds as $contract) {
            $activities[] = $this->formatContractEndActivity($contract);
        }

        // Sort by timestamp
        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
        });

        return $activities;
    }

    protected function formatCommentActivity(Comment $comment): array
    {
        $subject = $comment->subject;
        $metadata = $comment->metadata;
        
        $entityType = class_basename($comment->subject_type);
        $entityName = match($entityType) {
            'Supplier' => $subject?->name ?? 'Fornitore sconosciuto',
            'Contact' => $subject?->name ?? 'Contatto sconosciuto',
            'Contract' => $subject?->title ?? 'Contratto sconosciuto',
            default => 'Entità sconosciuta',
        };

        $title = "Commento su {$entityType}: {$entityName}";
        $description = strip_tags($comment->comment);

        $badges = [];
        $badges[] = ['label' => 'Commento', 'color' => 'primary', 'icon' => 'heroicon-o-chat-bubble-left-right'];
        
        $entityBadgeColor = match($entityType) {
            'Supplier' => 'success',
            'Contact' => 'info',
            'Contract' => 'warning',
            default => 'gray',
        };
        $badges[] = ['label' => $entityType, 'color' => $entityBadgeColor, 'icon' => $this->getEntityIcon($entityType)];

        if ($metadata && isset($metadata['source'])) {
            $sourceBadge = match($metadata['source']) {
                'gmail' => ['label' => 'Gmail', 'color' => 'danger', 'icon' => 'heroicon-o-envelope'],
                'web' => ['label' => 'Web', 'color' => 'primary', 'icon' => 'heroicon-o-globe-alt'],
                'api' => ['label' => 'API', 'color' => 'warning', 'icon' => 'heroicon-o-code-bracket'],
                default => null,
            };
            if ($sourceBadge) {
                $badges[] = $sourceBadge;
            }
        }

        if ($metadata && ($metadata['is_internal'] ?? false)) {
            $badges[] = ['label' => 'Riservato', 'color' => 'gray', 'icon' => 'heroicon-o-lock-closed'];
        }

        $entityUrl = $this->getEntityUrl($comment->subject_type, $comment->subject_id);

        return [
            'title' => $title,
            'description' => $description,
            'badges' => $badges,
            'status' => $entityBadgeColor,
            'timestamp' => $comment->created_at->toDateTimeString(),
            'created_at' => $comment->created_at,
            'activity_type' => 'comment',
            'entity_url' => $entityUrl,
        ];
    }

    protected function formatContractStartActivity(Contract $contract): array
    {
        $badges = [
            ['label' => 'Inizio Contratto', 'color' => 'success', 'icon' => 'heroicon-o-play-circle'],
            ['label' => 'Contratto', 'color' => 'warning', 'icon' => 'heroicon-o-document-text'],
        ];

        if ($contract->amount_total) {
            $badges[] = [
                'label' => '€' . number_format($contract->amount_total, 0, ',', '.'),
                'color' => 'info',
                'icon' => 'heroicon-o-currency-euro'
            ];
        }

        $supplierName = $contract->supplier?->name ?? 'Fornitore sconosciuto';

        return [
            'title' => "Inizio: {$contract->title}",
            'description' => "Fornitore: {$supplierName}",
            'badges' => $badges,
            'status' => 'success',
            'timestamp' => $contract->start_date,
            'created_at' => Carbon::parse($contract->start_date),
            'activity_type' => 'contract-start',
            'entity_url' => \App\Filament\Resources\ContractResource::getUrl('edit', ['record' => $contract->id]),
        ];
    }

    protected function formatContractEndActivity(Contract $contract): array
    {
        $endDate = Carbon::parse($contract->end_date);
        $isFuture = $endDate->isFuture();
        
        $badges = [];
        
        if ($isFuture) {
            $badges[] = ['label' => 'Scadenza Contratto', 'color' => 'danger', 'icon' => 'heroicon-o-stop-circle'];
            
            $daysUntil = now()->diffInDays($endDate, false);
            if ($daysUntil > -30 && $daysUntil <= 90) {
                $badges[] = ['label' => 'Imminente', 'color' => 'danger', 'icon' => 'heroicon-o-exclamation-triangle'];
            } elseif ($daysUntil > 90 && $daysUntil <= 180) {
                $badges[] = ['label' => 'Prossima', 'color' => 'warning', 'icon' => 'heroicon-o-exclamation-circle'];
            }
        } else {
            $badges[] = ['label' => 'Fine Contratto', 'color' => 'gray', 'icon' => 'heroicon-o-check-circle'];
        }

        $badges[] = ['label' => 'Contratto', 'color' => 'warning', 'icon' => 'heroicon-o-document-text'];

        if ($contract->renewal_mode === 'automatic') {
            $badges[] = ['label' => 'Auto-rinnovo', 'color' => 'success', 'icon' => 'heroicon-o-arrow-path'];
        }

        $supplierName = $contract->supplier?->name ?? 'Fornitore sconosciuto';

        return [
            'title' => ($isFuture ? 'Scadenza: ' : 'Terminato: ') . $contract->title,
            'description' => "Fornitore: {$supplierName}",
            'badges' => $badges,
            'status' => $isFuture ? 'danger' : 'gray',
            'timestamp' => $contract->end_date,
            'created_at' => $endDate,
            'activity_type' => 'contract-end',
            'entity_url' => \App\Filament\Resources\ContractResource::getUrl('edit', ['record' => $contract->id]),
        ];
    }

    protected function getEntityIcon(string $entityType): string
    {
        return match($entityType) {
            'Supplier' => 'heroicon-o-building-office',
            'Contact' => 'heroicon-o-user',
            'Contract' => 'heroicon-o-document-text',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    protected function getEntityUrl(string $subjectType, int $subjectId): ?string
    {
        return match($subjectType) {
            'App\Models\Supplier' => \App\Filament\Resources\SupplierResource::getUrl('edit', ['record' => $subjectId]),
            'App\Models\Contact' => \App\Filament\Resources\ContactResource::getUrl('view', ['record' => $subjectId]),
            'App\Models\Contract' => \App\Filament\Resources\ContractResource::getUrl('edit', ['record' => $subjectId]),
            default => null,
        };
    }
}
