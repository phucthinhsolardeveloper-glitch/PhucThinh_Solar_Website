<?php

namespace App\Filament\Resources\Reviews;

use App\Domain\Reviews\ReviewModeration;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\AdminActor;
use App\Filament\Support\PublicLink;
use App\Models\Review;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Customer reviews wait here until approved; staff can answer under a review. */
class ReviewResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'reviews';

    protected static ?string $model = Review::class;

    protected static ?string $modelLabel = 'đánh giá';

    protected static ?string $pluralModelLabel = 'Đánh giá sản phẩm';

    protected static string|UnitEnum|null $navigationGroup = 'Khách hàng';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    public const STATUSES = ['pending' => 'Chờ duyệt', 'approved' => 'Đang hiện', 'hidden' => 'Đã ẩn'];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canModerate(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->canPerform('reviews_moderate');
    }

    public static function canEdit(Model $record): bool
    {
        return self::canModerate();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('parent_id');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Review::query()->whereNull('parent_id')->where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Người đánh giá')->disabled(),
            Select::make('stars')->label('Số sao')->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])->disabled(),
            TextInput::make('title')->label('Tiêu đề'),
            Select::make('status')->label('Tình trạng')->options(self::STATUSES)->required(),
            Textarea::make('content')->label('Nội dung')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Gửi lúc')->dateTime('d/m/Y')->sortable(),
                TextColumn::make('product.name')->label('Sản phẩm')->limit(40)->searchable(),
                TextColumn::make('stars')->label('Sao')->formatStateUsing(fn ($state) => str_repeat('★', (int) $state)),
                TextColumn::make('content')->label('Nội dung')->limit(70)->description(fn (Review $record) => $record->name),
                TextColumn::make('status')->label('Tình trạng')->badge()->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning', 'approved' => 'success', default => 'gray'
                    }),
            ])
            ->filters([SelectFilter::make('status')->label('Tình trạng')->options(self::STATUSES)])
            ->recordActions([PublicLink::action(),
                Action::make('approve')->label('Duyệt')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Review $record) => self::canModerate() && $record->status !== 'approved')
                    ->action(fn (Review $record) => app(ReviewModeration::class)->moderate($record, AdminActor::current(), 'approved')),
                Action::make('hide')->label('Ẩn')->icon(Heroicon::OutlinedEyeSlash)->requiresConfirmation()
                    ->visible(fn (Review $record) => self::canModerate() && $record->status === 'approved')
                    ->action(fn (Review $record) => app(ReviewModeration::class)->moderate($record, AdminActor::current(), 'hidden')),
                Action::make('reply')->label('Trả lời')->icon(Heroicon::OutlinedChatBubbleLeft)
                    ->visible(fn (Review $record) => self::canModerate() && $record->status === 'approved')
                    ->schema([Textarea::make('content')->label('Câu trả lời của Phúc Thịnh Solar')->rows(4)->required()->maxLength(5000)])
                    ->action(fn (Review $record, array $data) => app(ReviewModeration::class)->reply($record, AdminActor::current(), $data['content'])),
                Action::make('viewMedia')->label('Xem media')->icon(Heroicon::OutlinedPhoto)
                    ->visible(fn (Review $record) => $record->media()->exists())
                    ->modal()->modalHeading('Ảnh và video đánh giá')->modalSubmitAction(false)->modalCancelActionLabel('Đóng')
                    ->modalContent(fn (Review $record) => view('filament.review-media', ['media' => $record->media()->get()])),
                EditAction::make()->visible(fn () => self::canModerate())
                    ->using(fn (Review $record, array $data) => app(ReviewModeration::class)->edit($record, AdminActor::current(), $data)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveMany')->label('Duyệt các đánh giá đã chọn')
                        ->visible(fn () => self::canModerate())->requiresConfirmation()
                        ->action(fn (Collection $records) => app(ReviewModeration::class)->moderateMany($records, AdminActor::current(), 'approved'))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('hideMany')->label('Ẩn các đánh giá đã chọn')->requiresConfirmation()
                        ->visible(fn () => self::canModerate())
                        ->action(fn (Collection $records) => app(ReviewModeration::class)->moderateMany($records, AdminActor::current(), 'hidden'))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageReviews::route('/')];
    }
}
