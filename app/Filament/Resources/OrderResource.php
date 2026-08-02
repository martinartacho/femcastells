<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Comandes';

    protected static \UnitEnum|string|null $navigationGroup = 'Botiga';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canDo('botiga') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canDo('botiga', 'write') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canDo('botiga', 'write') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Comanda')->columns(2)->schema([
                Placeholder::make('reference_code')
                    ->label('Codi de referència')
                    ->content(fn (Order $record) => $record->formattedReferenceCode()),
                Placeholder::make('buyer')
                    ->label('Comprador')
                    ->content(fn (Order $record) => $record->buyerLabel()),
                Placeholder::make('buyer_email')
                    ->label('Correu')
                    ->content(fn (Order $record) => $record->buyer_email ?? '—'),
                Placeholder::make('payment_method')
                    ->label('Mètode de pagament')
                    ->content(fn (Order $record) => Order::PAYMENT_METHODS[$record->payment_method] ?? $record->payment_method),
                Placeholder::make('subtotal')
                    ->label('Subtotal')
                    ->content(fn (Order $record) => number_format((float) $record->subtotal, 2) . ' €'),
                Placeholder::make('tax_total')
                    ->label('IVA')
                    ->content(fn (Order $record) => number_format((float) $record->tax_total, 2) . ' €'),
                Placeholder::make('total')
                    ->label('Total')
                    ->content(fn (Order $record) => number_format((float) $record->total, 2) . ' €'),
                Placeholder::make('paid_at')
                    ->label('Pagat el')
                    ->content(fn (Order $record) => $record->paid_at?->format('d/m/Y H:i') ?? '—'),
                Placeholder::make('buyer_confirmed_at')
                    ->label('Comprador diu que ha enviat')
                    ->content(fn (Order $record) => $record->buyer_confirmed_at?->format('d/m/Y H:i') ?? '—')
                    ->visible(fn (Order $record) => $record->isBizum()),
                Placeholder::make('stripe_payment_intent_id')
                    ->label('Payment Intent (Stripe)')
                    ->content(fn (Order $record) => $record->stripe_payment_intent_id ?? '—')
                    ->visible(fn (Order $record) => $record->isStripe())
                    ->columnSpanFull(),
                Placeholder::make('notes')
                    ->label('Observacions del comprador')
                    ->content(fn (Order $record) => $record->notes ?: '—')
                    ->columnSpanFull(),

                Select::make('status')
                    ->label('Estat')
                    ->options(Order::STATUSES)
                    ->required()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('reference_code')
                    ->label('Codi')
                    ->state(fn (Order $record) => $record->formattedReferenceCode())
                    ->fontFamily('mono'),

                TextColumn::make('buyer')
                    ->label('Comprador')
                    ->state(fn (Order $record) => $record->buyerLabel()),

                TextColumn::make('status')
                    ->label('Estat')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Order::STATUS_PAID      => 'success',
                        Order::STATUS_PENDING   => 'warning',
                        Order::STATUS_CANCELLED => 'gray',
                        Order::STATUS_REFUNDED  => 'danger',
                        default                 => 'gray',
                    }),

                TextColumn::make('payment_method')
                    ->label('Mètode')
                    ->badge()
                    ->color(fn (string $state) => $state === Order::PAYMENT_METHOD_BIZUM ? 'info' : 'gray')
                    ->formatStateUsing(fn (string $state) => Order::PAYMENT_METHODS[$state] ?? $state),

                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' €')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->label('Pagat el')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),

                TextColumn::make('buyer_confirmed_at')
                    ->label('Bizum enviat (comprador)')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estat')
                    ->options(Order::STATUSES)
                    ->native(false),
                SelectFilter::make('payment_method')
                    ->label('Mètode')
                    ->options(Order::PAYMENT_METHODS)
                    ->native(false),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('markPaid')
                        ->label('Marcar pagat')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription(fn (Order $record) => $record->buyer_confirmed_at
                            ? 'El comprador ha indicat que ja ha enviat el Bizum.'
                            : 'El comprador encara no ha confirmat l\'enviament.')
                        ->action(fn (Order $record) => $record->update([
                            'status'  => Order::STATUS_PAID,
                            'paid_at' => now(),
                        ]))
                        ->visible(fn (Order $record) => $record->isBizum() && $record->status === Order::STATUS_PENDING
                            && (auth()->user()?->canDo('botiga', 'write') ?? false)),

                    Action::make('cancelOrder')
                        ->label('Anul·lar')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Order $record) => $record->update(['status' => Order::STATUS_CANCELLED]))
                        ->visible(fn (Order $record) => $record->isBizum() && $record->status === Order::STATUS_PENDING
                            && (auth()->user()?->canDo('botiga', 'write') ?? false)),

                    EditAction::make()->label('Veure'),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit'  => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
