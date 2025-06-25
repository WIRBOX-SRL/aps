<?php

namespace App\Filament\Pages;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\Concerns;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravelcm\Subscriptions\Models\Plan;
use TomatoPHP\FilamentSubscriptions\Events\CancelPlan;
use TomatoPHP\FilamentSubscriptions\Events\ChangePlan;
use TomatoPHP\FilamentSubscriptions\Events\RenewPlan;
use TomatoPHP\FilamentSubscriptions\Events\SubscribePlan;
use TomatoPHP\FilamentSubscriptions\Facades\FilamentSubscriptions;
use TomatoPHP\FilamentSubscriptions\Http\Middleware\VerifyBillableIsSubscribed;
use TomatoPHP\FilamentSubscriptions\Models\Subscription;
use Illuminate\Support\Facades\Redirect;


use function Pest\Laravel\call;

class Billing extends Page implements HasActions
{
    use Concerns\HasTopbar;
    use InteractsWithActions;

    protected static string $layout = 'filament-subscriptions::layouts.billing';

    protected static string | array $withoutRouteMiddleware = VerifyBillableIsSubscribed::class;

    public static function registerRoutes(Panel $panel): void
    {
        Route::name('tenant.')->group(fn () => static::routes($panel));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getRouteName(?string $panel = null): string
    {
        $routeName = 'filament';
        if ($panel !== null) {
            // we don`t use Filament::getCurrentPanel(), because if `$panel` presented it will be found or throwed exception
            $routeName .= '.' . Filament::getPanel($panel)->getId();
        }
        return $routeName . '.tenant.billing';
    }

    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => $this->hasTopbar(),
        ];
    }

    public function hasLogo(): bool
    {
        return true;
    }

    protected static ?string $title = 'Billing';

    protected static string $view = 'filament-subscriptions::pages.billing';

    public Authenticatable $user;
    public Collection $plans;
    public ?Subscription $currentSubscription;
    public string $currentPanel;

public function mount()
{
    $this->user = Filament::auth()->getUser();
    $this->plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
    $this->currentSubscription = $this->user->planSubscriptions()->first();
    $this->currentPanel = Filament::getCurrentPanel()->getId();

    if ($this->currentSubscription === null) {
        $mainPlan = Plan::updateOrCreate(
            ['slug' => 'main'],
            [
                'name' => ['en' => 'Main'],
                'price' => 0,
                'currency' => 'USD',
                'is_active' => true,
                'trial_period' => 1264,
                'trial_interval' => 'year',
                'sort_order' => 2,
            ]
        );

        $this->subscribe($mainPlan->id, true);

        Notification::make()
            ->title('Abonament activat')
            ->body('Ai fost abonat automat la planul gratuit.')
            ->success()
            ->send();

        return redirect(Filament::getUrl());
    }

    return null;
}


    public function changePlanAction(?Plan $plan=null): Action
    {
        $currentSubscription = $this->user->planSubscriptions()->first();
        $isCurrentPlan = $plan
            && $currentSubscription
            && $currentSubscription->plan()->is($plan);
        $isCurrentPlanAndActive = $isCurrentPlan && $currentSubscription?->active();

        return Action::make('changePlanAction')
            ->requiresConfirmation()
            ->label(fn(): ?string => $this->textByPlan($plan))
            ->modalHeading(fn(array $arguments): ?string => $this->textByPlan(Plan::find($arguments['plan']['id'])))
            ->disabled(fn(): bool => $isCurrentPlanAndActive)
            ->color(fn(): string => match (true) {
                $isCurrentPlanAndActive => 'success',
                $isCurrentPlan && !$currentSubscription->active() => 'warning',
                default => 'primary',
            })
            ->icon(fn():string => match (true) {
                $isCurrentPlanAndActive => 'heroicon-s-check-circle',
                $isCurrentPlan && $currentSubscription->canceled() => 'heroicon-s-arrow-path-rounded-square',
                $isCurrentPlan && $currentSubscription->ended() => 'heroicon-s-arrow-path-rounded-square',
                default => 'heroicon-s-arrows-right-left',
            })
            ->action(function(array $arguments){
                $this->subscribe($arguments['plan']['id']);
            });
    }

    public function cancelPlanAction(): Action
    {
        return Action::make('cancelPlanAction')
            ->requiresConfirmation()
            ->label(trans('filament-subscriptions::messages.view.cancel_subscription'))
            ->action(function(){
                $this->cancel();
            });
    }

    public function subscribe(int $plan, bool $main = false)
    {
        if (!$plan) {
            $this->handleNotificationWithRedirectToPanel(
                __('filament-subscriptions::messages.notifications.invalid.title'),
                __('filament-subscriptions::messages.notifications.invalid.message'),
                'danger',
            );
        }

        $plan = Plan::find($plan);

        if ($this->currentSubscription) {
            if ($this->currentSubscription->plan_id === $plan->id) {
                if ($this->currentSubscription->active()) {
                    return $this->handleNotificationWithRedirectToPanel(
                        __('filament-subscriptions::messages.notifications.info.title'),
                        __('filament-subscriptions::messages.notifications.info.message'),
                    );
                }

                $this->currentSubscription->canceled_at =  $this->currentSubscription->cancels_at ? Carbon::parse($this->currentSubscription->cancels_at)->addDays(1) : null;
                $this->currentSubscription->cancels_at = $this->currentSubscription->cancels_at ? Carbon::parse($this->currentSubscription->cancels_at)->addDays(1) : null;
                $this->currentSubscription->ends_at =  $this->currentSubscription->cancels_at ? Carbon::parse($this->currentSubscription->cancels_at)->addDays(1) : null;
                $this->currentSubscription->save();
                $this->currentSubscription->renew($plan);

                Event::dispatch(new RenewPlan([
                    "old" => $this->currentSubscription->plan,
                    "new" => $plan,
                    "subscription" => $this->currentSubscription
                ]));

                if(!$main){
                    return call_user_func(FilamentSubscriptions::getAfterRenew(),[
                        "old" => $this->currentSubscription->plan,
                        "new" => $plan,
                        "subscription" => $this->currentSubscription
                    ]);
                }

                return $this->handleNotificationWithRedirectToPanel(
                    __('filament-subscriptions::messages.notifications.renew.title'),
                    __('filament-subscriptions::messages.notifications.renew.message'),
                    'success',
                );
            }

            Event::dispatch(new ChangePlan([
                "old" => $this->currentSubscription->plan,
                "new" => $plan,
                "subscription" => $this->currentSubscription
            ]));

            $this->currentSubscription->changePlan($plan);

            if(!$main){
                return call_user_func(FilamentSubscriptions::getAfterChange(), [
                    "old" => $this->currentSubscription->plan,
                    "new" => $plan,
                    "subscription" => $this->currentSubscription
                ]);
            }

            return $this->handleNotificationWithRedirectToPanel(
                __('filament-subscriptions::messages.notifications.change.title'),
                __('filament-subscriptions::messages.notifications.change.message'),
                'success',
            );
        }

        // No current subscription
        $this->user->newPlanSubscription('main', $plan);

        Event::dispatch(new SubscribePlan([
            "old" => null,
            "new" => $plan,
            "subscription" => $this->user->planSubscriptions()->first()
        ]));

        if(!$main){
            return call_user_func(FilamentSubscriptions::getAfterSubscription(), [
                "old" => null,
                "new" => $plan,
                "subscription" => $this->user->planSubscriptions()->first()
            ]);
        }

        return $this->handleNotificationWithRedirectToPanel(
            __('filament-subscriptions::messages.notifications.subscription.title'),
            __('filament-subscriptions::messages.notifications.subscription.message'),
            'success',
        );
    }

    public function cancel()
    {
        $activeSubscriptions = $this->user->activePlanSubscriptions();

        if ($activeSubscriptions->isEmpty()) {
            return $this->handleNotificationWithRedirectToPanel(
                __('filament-subscriptions::messages.notifications.no_active.title'),
                __('filament-subscriptions::messages.notifications.no_active.message'),
                'danger',
            );
        }

        try {
            foreach ($activeSubscriptions as $subscription) {
                Event::dispatch(new CancelPlan([
                    "old" => null,
                    "new" => $subscription->plan,
                    "subscription" => $subscription
                ]));

                $subscription->cancel(true);
            }


            return call_user_func(FilamentSubscriptions::getAfterCanceling(), [
                "old" => null,
                "new" => $subscription->plan,
                "subscription" => $subscription
            ]);
        } catch (\Exception $e) {
            return $this->handleNotificationWithRedirectToPanel(
                __('filament-subscriptions::messages.notifications.cancel_invalid.title'),
                __('filament-subscriptions::messages.notifications.cancel_invalid.message'),
                'danger',
            );
        }
    }

    private function textByPlan(?Plan $plan = null): ?string {
        if (!$plan) {
            return null;
        }

        if (!$hasSubscription = $this->user->planSubscriptions()->first()) {
            return __('filament-subscriptions::messages.view.subscribe');
        }

        if ($hasSubscription->plan()->is($plan)) {
            return match (true) {
                $hasSubscription->active() => __('filament-subscriptions::messages.view.current_subscription'),
                $hasSubscription->canceled() => __('filament-subscriptions::messages.view.resubscribe'),
                $hasSubscription->ended() => __('filament-subscriptions::messages.view.renew_subscription'),
            };
        }

        return __('filament-subscriptions::messages.view.change_subscription');
    }

private function handleNotificationWithRedirectToPanel(
    string $title,
    string $body,
    string $status = 'info'
) {
    Notification::make()
        ->title($title)
        ->body($body)
        ->status($status)
        ->send();

    return redirect(Filament::getUrl());
}


}
