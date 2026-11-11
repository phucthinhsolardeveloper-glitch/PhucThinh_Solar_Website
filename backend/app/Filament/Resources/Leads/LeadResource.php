<?php

namespace App\Filament\Resources\Leads;

use App\Domain\Commerce\CustomerHistory;
use App\Domain\Commerce\LeadTriage;
use App\Domain\Leads\LeadSource;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\AdminActor;
use App\Models\Lead;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use UnitEnum;

/** Contact requests, product consultations and dealer sign-ups from the site's forms. */
class LeadResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'leads';

    protected static ?string $model = Lead::class;

    protected static ?string $modelLabel = 'yêu cầu';

    protected static ?string $pluralModelLabel = 'Liên hệ & tư vấn';

    protected static string|UnitEnum|null $navigationGroup = 'Khách hàng';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    public const KINDS = ['contact' => 'Liên hệ', 'consultation' => 'Tư vấn sản phẩm', 'dealer' => 'Đăng ký đại lý'];

    public const STATUSES = ['new' => 'Mới', 'contacted' => 'Đã liên hệ', 'done' => 'Đã xong', 'spam' => 'Rác'];

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'email'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Lead $record */
        return ['Điện thoại' => (string) $record->phone, 'Tình trạng' => self::STATUSES[$record->status] ?? $record->status];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return self::getUrl().'?tableSearch='.urlencode((string) $record->getAttribute('phone'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $new = Lead::query()->where('status', 'new')->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function form(Schema $schema): Schema
    {
        $show = fn (string $name, string $label) => TextInput::make($name)->label($label)->disabled()->dehydrated(false);

        return $schema->columns(2)->components([
            Section::make('Khách gửi')->columns(2)->columnSpanFull()->schema([
                $show('name', 'Họ tên'), $show('phone', 'Điện thoại'), $show('email', 'Email'), $show('company', 'Công ty'),
                $show('address', 'Địa chỉ / khu vực'), $show('subject', 'Chủ đề'),
                TextInput::make('product_name')->label('Sản phẩm quan tâm')->disabled()->dehydrated(false)
                    ->formatStateUsing(fn (?Lead $record) => $record?->product?->name),
                TextInput::make('source_url')->label('Trang gửi yêu cầu')->disabled()->dehydrated(false)
                    ->formatStateUsing(fn (?Lead $record) => LeadSource::normalize($record?->source_url)),
                $show('audience', 'Loại hình'), $show('quantity', 'Công suất / số lượng'),
                Textarea::make('message')->label('Nội dung')->disabled()->dehydrated(false)->columnSpanFull(),
            ]),
            Select::make('status')->label('Tình trạng')->options(self::STATUSES)->required(),
            Select::make('assigned_to')->label('Người phụ trách')->options(fn () => User::query()->where('is_active', true)->get()
                ->filter(fn (User $user) => $user->hasPermission('leads'))->pluck('name', 'id')->all())->searchable(),
            DateTimePicker::make('follow_up_at')->label('Hẹn theo dõi')->seconds(false),
            Textarea::make('notes')->label('Ghi chú xử lý')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['assignee', 'product']))
            ->columns([
                TextColumn::make('created_at')->label('Gửi lúc')->dateTime('d/m H:i')->sortable()->wrap(false)
                    ->description(fn (Lead $record) => self::KINDS[$record->kind] ?? $record->kind),
                TextColumn::make('name')->label('Khách')->searchable(['name', 'phone', 'email'])
                    ->description(fn (Lead $record) => trim($record->phone.(($h = app(CustomerHistory::class)->label($record->phone, exceptLead: (int) $record->id)) !== '' ? ' · '.$h : ''))),
                TextColumn::make('source_url')->label('Trang nguồn')->placeholder('—')->limit(30)->toggleable()->searchable()->wrap(false)
                    ->formatStateUsing(fn ($state) => LeadSource::normalize($state) ?? '—')
                    ->url(fn (Lead $record) => ($source = LeadSource::normalize($record->source_url)) ? url($source) : null, true),
                TextColumn::make('message')->label('Nội dung')->limit(90)->placeholder('—')->toggleable()->grow()
                    ->description(fn (Lead $record) => data_get($record, 'product.name') ? 'Sản phẩm: '.data_get($record, 'product.name') : null),
                TextColumn::make('status')->label('Tình trạng')->badge()->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => $state === 'new' ? 'warning' : ($state === 'done' ? 'success' : 'gray'))
                    ->description(fn (Lead $record) => (data_get($record, 'assignee.name') ?? 'Chưa phân công')
                        .($record->follow_up_at ? ' · hẹn '.Carbon::parse($record->follow_up_at)->format('d/m H:i') : ''))
                    ->icon('heroicon-m-pencil-square')->iconPosition('after')->tooltip('Bấm để đổi tình trạng')
                    ->action(self::quickStatusAction()),
                TextColumn::make('follow_up_at')->label('Hẹn theo dõi')->dateTime('d/m/Y H:i')->sortable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kind')->label('Loại')->options(self::KINDS),
                SelectFilter::make('status')->label('Tình trạng')->options(self::STATUSES),
                SelectFilter::make('assigned_to')->label('Phụ trách')->options(fn () => User::query()->where('is_active', true)->get()
                    ->filter(fn (User $user) => $user->hasPermission('leads'))->pluck('name', 'id')->all()),
                Filter::make('overdue')->label('Quá hạn theo dõi')->query(fn (Builder $query) => $query
                    ->whereNotIn('status', ['done', 'spam'])->where('follow_up_at', '<', now())),
            ])
            ->recordAction('edit')
            ->recordActions([
                EditAction::make()->label('Xem / xử lý')
                    ->using(fn (Lead $record, array $data) => app(LeadTriage::class)->update($record, $data, AdminActor::current())),
                Action::make('history')->label('Lịch sử')->icon(Heroicon::OutlinedClock)->iconButton()->tooltip('Lịch sử xử lý')
                    ->modal()->modalHeading('Lịch sử xử lý')->modalSubmitAction(false)->modalCancelActionLabel('Đóng')
                    ->modalContent(fn (Lead $record) => view('filament.lead-history', ['events' => $record->events()->with('actor')->latest()->limit(100)->get()])),
            ]);
    }

    /** Status, follow-up and notes from the list; assignment and everything else stay in "Xem / xử lý". */
    public static function quickStatusAction(): Action
    {
        return Action::make('quickStatus')->label('Đổi tình trạng')
            ->modalHeading(fn (Lead $record) => $record->name.' · '.$record->phone)
            ->modalDescription(fn (Lead $record) => trim(app(CustomerHistory::class)->label($record->phone, exceptLead: (int) $record->id).' '.str((string) $record->message)->limit(160)->toString()) ?: null)
            ->modalWidth('md')->modalSubmitActionLabel('Lưu')
            ->fillForm(fn (Lead $record) => [
                'status' => $record->status, 'follow_up_at' => $record->follow_up_at, 'notes' => $record->notes,
            ])
            ->schema([
                Select::make('status')->label('Tình trạng')->options(self::STATUSES)->required()->selectablePlaceholder(false)->live(),
                DateTimePicker::make('follow_up_at')->label('Hẹn theo dõi')->seconds(false)
                    ->visible(fn ($get) => ! in_array($get('status'), ['done', 'spam'], true)),
                Textarea::make('notes')->label('Ghi chú xử lý')->rows(3),
            ])
            ->action(fn (Lead $record, array $data) => app(LeadTriage::class)->update($record, [
                'assigned_to' => $record->assigned_to,
                'follow_up_at' => $data['follow_up_at'] ?? null,
            ] + $data, AdminActor::current()));
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageLeads::route('/')];
    }
}
