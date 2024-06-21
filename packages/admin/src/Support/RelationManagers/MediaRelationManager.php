<?php

namespace Lunar\Admin\Support\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Lunar\Admin\Events\ModelMediaUpdated;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaRelationManager extends BaseRelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'media';

    public string $mediaCollection = 'default';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('custom_properties.name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('custom_properties.primary')
                    ->inline(false),
                Forms\Components\FileUpload::make('media')
                    ->columnSpan(2)
                    ->hiddenOn('edit')
                    ->storeFiles(false)
                    ->imageEditor()
                    ->required()
                    ->imageEditorAspectRatios([
                        null,
                        '16:9',
                        '4:3',
                        '1:1',
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(function () {
                return $this->getOwnerRecord()->getMediaCollectionTitle($this->mediaCollection) ?? Str::ucfirst($this->mediaCollection);
            })
            ->description(function () {
                return $this->getOwnerRecord()->getMediaCollectionDescription($this->mediaCollection) ?? '';
            })
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('collection_name', $this->mediaCollection)->orderBy('order_column'))
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->state(function (Media $record): string {
                        return $record->hasGeneratedConversion('small') ? $record->getUrl('small') : '';
                    }),
                Tables\Columns\TextColumn::make('file_name')
                    ->label('File'),
                Tables\Columns\TextColumn::make('custom_properties.name')
                    ->label('Name'),
                Tables\Columns\IconColumn::make('custom_properties.primary')
                    ->label('Primary')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model): Model {

                        return $this->getOwnerRecord()->addMediaFromString($data['media']->get())
                            ->usingFileName(
                                $data['media']->getClientOriginalName()
                            )
                            ->withCustomProperties([
                                'name' => $data['custom_properties']['name'],
                                'primary' => $data['custom_properties']['primary'],
                            ])
                            ->preservingOriginal()
                            ->toMediaCollection($this->mediaCollection);
                    })->after(function ($record) {
                        $owner = $this->getOwnerRecord();

                        $collection = $owner->getMedia($this->mediaCollection);
                        $collectionCount = $collection->count();
                        $isPrimary = (bool) $record->getCustomProperty('primary');
                        if ($isPrimary && $collectionCount > 1) {
                            $collection->reject(fn ($media) => $media->id == $record->id || $media->getCustomProperty('primary') === false)
                                ->each(fn ($media) => $media->setCustomProperty('primary', false)->save());
                        } elseif (! $isPrimary && ($collectionCount == 1 || $collection->filter(fn ($media) => $media->getCustomProperty('primary') === true)->isEmpty())) {
                            $record->setCustomProperty('primary', true)->save();
                        }

                        ModelMediaUpdated::dispatch($owner);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        $owner = $this->getOwnerRecord();

                        if ($record->getCustomProperty('primary')) {
                            $owner->getMedia($this->mediaCollection)
                                ->reject(fn ($media) => $media->id == $record->id || $media->getCustomProperty('primary') === false)
                                ->each(fn ($media) => $media->setCustomProperty('primary', false)->save());
                        } elseif ($owner->getMedia($this->mediaCollection)->count() == 1) {
                            $record->setCustomProperty('primary', true)->save();
                        } else {
                            $owner->getMedia($this->mediaCollection)
                                ->first(fn ($media) => $media->id != $record->id && $media->getCustomProperty('primary') === false)
                                ->setCustomProperty('primary', true)
                                ->save();
                        }

                        ModelMediaUpdated::dispatch($owner);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        $owner = $this->getOwnerRecord();

                        $collection = $owner->getMedia($this->mediaCollection);

                        if ($collection->filter(fn ($media) => $media->getCustomProperty('primary') === true)->isEmpty()) {
                            $collection->first()
                                ->setCustomProperty('primary', true)
                                ->save();
                        }
                    }),
                Action::make('view_open')
                    ->label('View')
                    ->icon('lucide-eye')
                    ->url(fn (Media $record): string => $record->getUrl())
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->after(function () {
                        $owner = $this->getOwnerRecord();

                        $collection = $owner->getMedia($this->mediaCollection);

                        if ($collection->filter(fn ($media) => $media->getCustomProperty('primary') === true)->isEmpty()) {
                            $collection->first()
                                ->setCustomProperty('primary', true)
                                ->save();
                        }

                        ModelMediaUpdated::dispatch($owner);
                    }),
                ]),
            ])
            ->reorderable('order_column');
    }
}
