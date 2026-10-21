<?php
/**
 * Notifications panel element.
 *
 * Server-rendered port of the upstream notifications inspector: grouped
 * delivery attempts (notifiable plus notification class) with a status
 * filter, search across class/recipient/channels/failures, and Delivery
 * (per-channel articles), Payload (notification data plus channel
 * evidence) and Source (callsite plus capped frames) tabs per group.
 * Interactive states are server-rendered (`aria-pressed`, `hidden`); the
 * boot script owns search/filter/selection over the `data-dk-*` hooks.
 * No JavaScript ships with the panel.
 *
 * CakePHP notes (no fake data): only observed `sent`, `failed`,
 * `skipped` and `pending` statuses render — queue states are never
 * invented since queued sends emit no events in the web request. Mail
 * deliveries show no preview link because the mail panel stores no
 * transport message id to match on.
 *
 * @var \Crustum\DebugBar\View\AjaxView $this
 * @var array $groups Grouped attempts from `NotificationsPanel::data()`
 * @var array $summary Counters with `count` and `attention`
 */
use function Cake\Core\h;

$groups = array_values((array)($groups ?? []));
$summary = (array)($summary ?? []);
$facets = (array)($facets ?? []);
$toFacets = (array)($facets['to'] ?? []);
$classFacets = (array)($facets['class'] ?? []);
$facetCount = static function (string $token) use ($groups): int {
    $count = 0;
    foreach ($groups as $group) {
        $tokens = explode(' ', (string)((array)$group)['filter'] ?? '');
        if (in_array($token, $tokens, true)) {
            $count++;
        }
    }

    return $count;
};
$total = (int)($summary['count'] ?? count($groups));
$attentionCount = 0;
$sentCount = 0;
foreach ($groups as $group) {
    $group = (array)$group;
    if ((string)($group['status'] ?? 'sent') === 'sent') {
        $sentCount++;
    } else {
        $attentionCount++;
    }
}

$segmentedTabClass = 'dk:relative dk:flex dk:min-w-0 dk:items-baseline dk:justify-center dk:gap-1.5 dk:whitespace-nowrap dk:rounded-md dk:border dk:border-transparent dk:bg-transparent dk:px-3 dk:py-1.5 dk:text-xs dk:font-semibold dk:text-zinc-500 dk:transition-[background-color,color,box-shadow] dk:hover:bg-white/60 dk:hover:text-zinc-950 dk:focus-visible:z-10 dk:focus-visible:outline-2 dk:focus-visible:outline-indigo-500 dk:aria-pressed:bg-white dk:aria-pressed:text-zinc-950 dk:aria-pressed:shadow-sm dk:aria-pressed:hover:bg-white dk:aria-pressed:hover:text-zinc-950 dk:aria-selected:bg-white dk:aria-selected:text-zinc-950 dk:aria-selected:shadow-sm dk:aria-selected:hover:bg-white dk:aria-selected:hover:text-zinc-950 dk:dark:text-zinc-400 dk:dark:hover:bg-zinc-800/70 dk:dark:hover:text-white dk:dark:aria-pressed:bg-zinc-800 dk:dark:aria-pressed:text-white dk:dark:aria-selected:bg-zinc-800 dk:dark:aria-selected:text-white';
$statusClass = static function (string $status): string {
    return match ($status) {
        'sent' => 'dk:text-emerald-600 dk:dark:text-emerald-300',
        'failed' => 'dk:text-red-600 dk:dark:text-red-300',
        'pending', 'skipped' => 'dk:text-sky-600 dk:dark:text-sky-300',
        default => 'dk:text-amber-600 dk:dark:text-amber-300',
    };
};
$firstKey = '';
foreach ($groups as $group) {
    $group = (array)$group;
    if ($firstKey === '') {
        $firstKey = (string)($group['group_id'] ?? '');
    }
}
?>
<div data-dk-notifications-panel data-dk-active-filter="all" class="dk:space-y-4 dk:lg:flex dk:lg:min-h-0 dk:lg:flex-1 dk:lg:flex-col dk:lg:space-y-0">
    <script type="application/json" data-dk-selection-config><?= (string)json_encode([
        'entry' => 'data-dk-notification-entry',
        'key' => 'data-dk-notification-key',
        'detail' => 'data-dk-notification-detail',
        'panel' => '[data-dk-notifications-panel]',
        'paint' => true,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>
    <?php if ($groups === []) : ?>
    <?= $this->element('Crustum/DebugBar.ui/empty_state', ['message' => 'No notifications were sent.']) ?>
    <?php else : ?>
    <?php
    ob_start();
        ?>
    <p
        data-dk-notifications-summary
        aria-live="polite"
        class="dk:min-w-0 dk:text-xs dk:font-semibold dk:text-zinc-600 dk:dark:text-zinc-300"
    >
        <?= number_format($total) ?> <?= $total === 1 ? 'notification' : 'notifications' ?>
        <span
            data-dk-visible-count
            hidden
            class="dk:ml-1 dk:text-xs dk:font-medium dk:text-zinc-500 dk:dark:text-zinc-400"
        ></span>
    </p>
    <div
        data-dk-inspector-list-controls
        class="dk:grid dk:items-start dk:gap-x-2 dk:gap-y-3 dk:grid-cols-[minmax(0,1fr)_8.75rem]"
    >
        <div class="dk:min-w-0">
            <label class="dk:relative dk:block dk:min-w-0">
                <span class="dk:sr-only">Search notifications</span>
                <input
                    data-dk-search="notifications"
                    type="search"
                    placeholder="Search notification or recipient"
                    aria-label="Search notifications"
                    autocomplete="off"
                    class="dk:h-9 dk:w-full dk:rounded-lg dk:border dk:border-zinc-200 dk:bg-white/70 dk:pr-3 dk:pl-8 dk:text-xs dk:outline-none dk:transition dk:placeholder:text-zinc-400 dk:focus:border-indigo-400 dk:focus:ring-2 dk:focus:ring-indigo-500/15 dk:dark:border-zinc-700 dk:dark:bg-zinc-900/70"
                />
                <?= $this->element('Crustum/DebugBar.ui/icon', ['name' => 'search', 'size' => '4', 'class' => 'dk:pointer-events-none dk:absolute dk:top-1/2 dk:left-2.5 dk:-translate-y-1/2 dk:text-zinc-400']) ?>
            </label>
        </div>
        <div class="dk:min-w-0">
            <label class="dk:relative dk:block">
                <span class="dk:sr-only">Filter notifications by status</span>
                <select
                    data-dk-select="notifications-filter"
                    class="dk:h-9 dk:w-full dk:appearance-none dk:rounded-lg dk:border dk:border-zinc-200 dk:bg-white/70 dk:pr-8 dk:pl-3 dk:text-xs dk:font-semibold dk:outline-none dk:transition dk:focus:border-indigo-400 dk:focus:ring-2 dk:focus:ring-indigo-500/15 dk:dark:border-zinc-700 dk:dark:bg-zinc-900/70"
                >
                    <option value="all" selected>All (<?= number_format($total) ?>)</option>
                    <option value="attention">Needs attention (<?= number_format($attentionCount) ?>)</option>
                    <option value="sent">Sent (<?= number_format($sentCount) ?>)</option>
                    <?php if ($toFacets !== []) : ?>
                    <optgroup label="To">
                        <?php foreach ($toFacets as $token => $label) : ?>
                        <option value="<?= h((string)$token) ?>"><?= h((string)$label) ?> (<?= number_format($facetCount((string)$token)) ?>)</option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                    <?php if ($classFacets !== []) : ?>
                    <optgroup label="Notification">
                        <?php foreach ($classFacets as $token => $label) : ?>
                        <option value="<?= h((string)$token) ?>"><?= h((string)$label) ?> (<?= number_format($facetCount((string)$token)) ?>)</option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                </select>
                <?= $this->element('Crustum/DebugBar.ui/icon', ['name' => 'chevron-down', 'size' => '3.5', 'class' => 'dk:pointer-events-none dk:absolute dk:top-1/2 dk:right-2.5 dk:-translate-y-1/2 dk:text-zinc-400']) ?>
            </label>
        </div>
    </div>
    <?php
    $controls = (string)ob_get_clean();
    ob_start();
        ?>
    <div data-dk-notifications-list aria-label="Notifications">
        <?php foreach ($groups as $group) :
            $group = (array)$group;
            $key = (string)($group['group_id'] ?? '');
            $status = (string)($group['status'] ?? 'sent');
            $recipient = (array)($group['recipient'] ?? []);
            $pressed = $key !== '' && $key === $firstKey;
            ?>
        <button
            type="button"
            data-dk-notification-entry
            data-dk-notification-key="<?= h($key) ?>"
            data-dk-filter-group="<?= h((string)($group['filter'] ?? ($status === 'sent' ? 'sent' : 'attention'))) ?>"
            data-dk-filter-attention="<?= $status === 'sent' ? 'false' : 'true' ?>"
            data-dk-search-text="<?= h((string)($group['search'] ?? '')) ?>"
            aria-pressed="<?= $pressed ? 'true' : 'false' ?>"
            class="dk:grid dk:h-auto dk:w-full dk:grid-cols-[minmax(0,1fr)_auto] dk:items-baseline dk:gap-x-3 dk:gap-y-1 dk:px-3 dk:py-2.5 dk:text-left dk:transition-colors dk:focus-visible:relative dk:focus-visible:z-10 dk:focus-visible:outline-2 dk:focus-visible:outline-indigo-500 dk:sm:py-3<?= $pressed ? ' dk:bg-indigo-50/65 dk:dark:bg-indigo-950/20' : ' dk:hover:bg-zinc-50/80 dk:dark:hover:bg-zinc-900/60' ?>"
        >
            <span class="dk:min-w-0 dk:truncate dk:text-xs dk:font-bold dk:text-zinc-900 dk:dark:text-zinc-100"><?= h((string)($group['label'] ?? '')) ?></span>
            <span class="dk:justify-self-end dk:text-xs dk:font-bold <?= h($statusClass($status)) ?>">
                <?= h((string)($group['status_label'] ?? '')) ?>
            </span>
            <span class="dk:col-start-1 dk:min-w-0 dk:truncate dk:text-xs dk:text-zinc-500 dk:dark:text-zinc-400">To <?= h((string)($recipient['label'] ?? '')) ?></span>
            <span class="dk:col-start-2 dk:justify-self-end dk:text-right dk:text-xs dk:font-semibold dk:tabular-nums dk:text-zinc-400">
                <?= h((string)($group['duration_label'] ?? '')) ?>
            </span>
        </button>
        <?php endforeach; ?>
    </div>
    <div data-dk-list-empty hidden>
        <?= $this->element('Crustum/DebugBar.ui/empty_state', ['message' => 'No notifications match these filters.']) ?>
    </div>
    <?php
    $listBody = (string)ob_get_clean();
    ob_start();
        ?>
    <div data-dk-detail-pane class="dk:min-w-0">
        <div data-dk-detail-empty hidden>No notification is selected. Adjust the filter or search.</div>
        <?php foreach ($groups as $group) :
            $group = (array)$group;
            $key = (string)($group['group_id'] ?? '');
            $status = (string)($group['status'] ?? 'sent');
            $recipient = (array)($group['recipient'] ?? []);
            $deliveries = array_values((array)($group['deliveries'] ?? []));
            $callsite = is_array($group['callsite'] ?? null) ? $group['callsite'] : null;
            $frames = array_values((array)($callsite['frames'] ?? []));
            $pressed = $key !== '' && $key === $firstKey;
            $tabScope = $key;
            ?>
        <section
            data-dk-notification-detail="<?= h($key) ?>"
            aria-label="Selected notification details"
            <?= $pressed ? '' : 'hidden' ?>
        >
            <div class="dk:flex dk:flex-col">
                <header class="dk:border-b dk:border-zinc-200/90 dk:p-3 dk:sm:p-4 dk:dark:border-zinc-800">
                    <div class="dk:grid dk:min-w-0 dk:grid-cols-[minmax(0,1fr)_auto] dk:items-start dk:gap-x-3 dk:gap-y-2">
                        <div class="dk:min-w-0">
                            <h3 class="dk:break-words dk:text-base dk:font-bold dk:leading-6"><?= h((string)($group['label'] ?? '')) ?></h3>
                            <code class="dk:mt-1 dk:block dk:break-all dk:bg-transparent dk:font-mono dk:text-xs dk:font-medium dk:leading-4 dk:text-zinc-400"><?= h((string)($group['notification'] ?? '')) ?></code>
                        </div>
                        <span class="dk:shrink-0 dk:text-xs dk:font-bold <?= h($statusClass($status)) ?>">
                            <?= h((string)($group['status_label'] ?? '')) ?>
                        </span>
                    </div>
                    <dl class="dk:mt-3 dk:grid dk:min-w-0 dk:gap-x-6 dk:gap-y-3 dk:grid-cols-1 dk:sm:grid-cols-2">
                        <div class="dk:min-w-0">
                            <dt class="dk:text-xs dk:font-medium dk:text-zinc-500 dk:dark:text-zinc-400">Recipient</dt>
                            <dd class="dk:min-w-0 dk:truncate dk:text-sm dk:font-semibold dk:text-zinc-800 dk:dark:text-zinc-200"><?= h((string)($recipient['label'] ?? '')) ?></dd>
                        </div>
                        <div class="dk:min-w-0">
                            <dt class="dk:text-xs dk:font-medium dk:text-zinc-500 dk:dark:text-zinc-400">Channels</dt>
                            <dd class="dk:min-w-0 dk:truncate dk:text-sm dk:font-semibold dk:text-zinc-800 dk:dark:text-zinc-200"><?= h((string)($group['channels_label'] ?? '')) ?></dd>
                        </div>
                        <div class="dk:min-w-0">
                            <dt class="dk:text-xs dk:font-medium dk:text-zinc-500 dk:dark:text-zinc-400">Duration</dt>
                            <dd class="dk:min-w-0 dk:truncate dk:text-sm dk:font-semibold dk:tabular-nums dk:text-zinc-800 dk:dark:text-zinc-200"><?= h((string)($group['duration_label'] ?? '')) ?></dd>
                        </div>
                        <div class="dk:min-w-0">
                            <dt class="dk:text-xs dk:font-medium dk:text-zinc-500 dk:dark:text-zinc-400">Queue</dt>
                            <dd class="dk:min-w-0 dk:truncate dk:text-sm dk:font-semibold dk:text-zinc-800 dk:dark:text-zinc-200"><?= h((string)($group['queue'] ?? '') !== '' ? (string)$group['queue'] : 'Synchronous') ?></dd>
                        </div>
                    </dl>
                </header>
                <div role="tablist" aria-label="Notification detail" data-dk-detail-tabs class="dk:border-b dk:border-zinc-200/90 dk:px-3 dk:py-2.5 dk:sm:px-4 dk:dark:border-zinc-800 dk:grid dk:grid-cols-1 dk:justify-items-center dk:gap-2">
                    <div role="presentation" class="dk:flex dk:min-w-0 dk:rounded-lg dk:bg-zinc-100/70 dk:p-1 dk:dark:bg-zinc-900/70">
                        <?php foreach (['delivery' => ['Delivery', 'activity'], 'payload' => ['Payload', 'database'], 'source' => ['Source', 'code']] as $tabKey => [$tabLabel, $tabIcon]) : ?>
                        <button
                            type="button"
                            role="tab"
                            aria-selected="<?= $tabKey === 'delivery' ? 'true' : 'false' ?>"
                            aria-label="<?= h($tabLabel) ?>"
                            data-dk-tab="<?= h($tabScope . ':' . $tabKey) ?>"
                            class="<?= h($segmentedTabClass) ?> dk:h-auto"
                        >
                            <?= $this->element('Crustum/DebugBar.ui/icon', ['name' => $tabIcon, 'size' => '3.5', 'class' => 'dk:sm:hidden']) ?>
                            <span class="dk:hidden dk:sm:inline"><?= h($tabLabel) ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div data-dk-tab-panel="<?= h($tabScope) ?>:delivery" class="dk:py-3 dk:sm:p-4">
                    <div class="dk:divide-y dk:divide-zinc-200/90 dk:border-y dk:border-zinc-200/90 dk:dark:divide-zinc-800 dk:dark:border-zinc-800">
                        <?php foreach ($deliveries as $delivery) :
                            $delivery = (array)$delivery;
                            $deliveryStatus = (string)($delivery['status'] ?? 'pending');
                            $deliveryIcon = $deliveryStatus === 'failed' ? 'warning' : ($deliveryStatus === 'sent' ? 'check' : 'clock');
                            $deliveryIconTone = $deliveryStatus === 'failed'
                                ? 'dk:bg-red-100 dk:text-red-600 dk:dark:bg-red-950 dk:dark:text-red-300'
                                : ($deliveryStatus === 'sent'
                                    ? 'dk:bg-emerald-100 dk:text-emerald-600 dk:dark:bg-emerald-950 dk:dark:text-emerald-300'
                                    : 'dk:bg-indigo-100 dk:text-indigo-600 dk:dark:bg-indigo-950 dk:dark:text-indigo-300');
                            ?>
                        <article data-dk-notification-delivery class="dk:relative">
                            <div class="dk:flex dk:items-start dk:justify-between dk:gap-3 dk:px-3 dk:py-2.5">
                                <div class="dk:flex dk:min-w-0 dk:items-start dk:gap-2.5">
                                    <span class="dk:flex dk:size-7 dk:shrink-0 dk:items-center dk:justify-center dk:rounded-lg <?= h($deliveryIconTone) ?>">
                                        <?= $this->element('Crustum/DebugBar.ui/icon', ['name' => $deliveryIcon, 'size' => '3.5']) ?>
                                    </span>
                                    <div class="dk:min-w-0">
                                        <h4 class="dk:truncate dk:text-xs dk:font-bold"><?= h((string)($delivery['channel_label'] ?? '')) ?></h4>
                                        <p class="dk:mt-0.5 dk:text-xs dk:font-medium <?= h($statusClass($deliveryStatus)) ?>"><?= h((string)($delivery['status_label'] ?? '')) ?></p>
                                        <span class="dk:mt-1 dk:block dk:break-all dk:bg-transparent dk:p-0 dk:text-xs <?= $delivery['destination_resolved'] ?? false ? 'dk:text-zinc-500 dk:dark:text-zinc-400' : 'dk:text-amber-700 dk:dark:text-amber-300' ?>"><?= h((string)($delivery['destination_label'] ?? '')) ?></span>
                                    </div>
                                </div>
                                <span class="dk:shrink-0 dk:text-xs dk:font-semibold dk:tabular-nums dk:text-zinc-400"><?= h((string)($delivery['duration_label'] ?? '')) ?></span>
                            </div>
                            <?php if ((string)($delivery['failure_message'] ?? '') !== '') : ?>
                            <div class="dk:border-t dk:border-red-200/80 dk:px-3 dk:py-2.5 dk:dark:border-red-950">
                                <p class="dk:text-xs dk:font-semibold dk:leading-5 dk:text-red-700 dk:dark:text-red-300"><?= h((string)$delivery['failure_message']) ?></p>
                                <?php if ((string)($delivery['exception_class'] ?? '') !== '') : ?>
                                <code class="dk:mt-1 dk:block dk:break-all dk:text-xs dk:text-red-500 dk:dark:text-red-400"><?= h((string)$delivery['exception_class']) ?></code>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ((string)($delivery['response_summary'] ?? '') !== '') : ?>
                            <div class="dk:border-t dk:border-zinc-200/80 dk:px-3 dk:py-2 dk:text-xs dk:text-zinc-500 dk:dark:border-zinc-800 dk:dark:text-zinc-400"><?= h((string)$delivery['response_summary']) ?></div>
                            <?php endif; ?>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div data-dk-tab-panel="<?= h($tabScope) ?>:payload" hidden class="dk:space-y-3 dk:p-3 dk:sm:space-y-5 dk:sm:p-4">
                    <div data-dk-tree-scope class="dk:min-w-0">
                        <?= $this->element('Crustum/DebugBar.ui/tree_filter', [
                            'label' => 'Search payload',
                            'placeholder' => 'Search payload keys and values',
                        ]) ?>
                    <?php if ($group['notification_data'] !== []) : ?>
                    <div>
                        <h4 class="dk:text-xs dk:font-bold">Application payload</h4>
                        <?= $this->element('Crustum/DebugBar.ui/debug_tree', [
                            'nodes' => (array)$group['notification_data'],
                        ]) ?>
                    </div>
                    <?php else : ?>
                    <p class="dk:text-xs dk:leading-5 dk:text-zinc-400">No application payload was captured.</p>
                    <?php endif; ?>
                    <?php foreach ($deliveries as $delivery) :
                        $delivery = (array)$delivery;
                        ?>
                    <section class="dk:border-t dk:border-zinc-200/90 dk:pt-3 dk:sm:pt-4 dk:dark:border-zinc-800">
                        <div class="dk:flex dk:items-center dk:justify-between dk:gap-3">
                            <h4 class="dk:text-xs dk:font-bold">Channel evidence</h4>
                            <span class="dk:text-xs dk:font-bold dk:text-zinc-500 dk:dark:text-zinc-400"><?= h((string)($delivery['channel_label'] ?? '')) ?></span>
                        </div>
                        <dl class="dk:mt-2">
                            <div class="dk:grid dk:gap-1 dk:py-2 dk:sm:grid-cols-[8rem_minmax(0,1fr)]">
                                <dt class="dk:text-xs dk:font-semibold dk:text-zinc-500 dk:dark:text-zinc-400">Destination</dt>
                                <dd class="dk:min-w-0 dk:break-all dk:bg-transparent dk:text-xs"><?= h((string)($delivery['destination_label'] ?? '')) ?></dd>
                            </div>
                            <div class="dk:grid dk:gap-1 dk:py-2 dk:sm:grid-cols-[8rem_minmax(0,1fr)]">
                                <dt class="dk:text-xs dk:font-semibold dk:text-zinc-500 dk:dark:text-zinc-400">Status</dt>
                                <dd class="dk:min-w-0 dk:bg-transparent dk:text-xs"><?= h((string)($delivery['status_label'] ?? '')) ?></dd>
                            </div>
                        </dl>
                        <?php if (!array_key_exists('response', $delivery) || $delivery['response'] === null) : ?>
                        <p class="dk:text-xs dk:leading-5 dk:text-zinc-400">No provider response was captured.</p>
                        <?php elseif (is_array($delivery['response']) && $delivery['response'] !== []) : ?>
                        <?= $this->element('Crustum/DebugBar.ui/debug_tree', [
                            'nodes' => $delivery['response'],
                        ]) ?>
                        <?php elseif (is_array($delivery['response'])) : ?>
                        <p class="dk:text-xs dk:leading-5 dk:text-zinc-400">Response is empty.</p>
                        <?php else : ?>
                        <p class="dk:mt-2 dk:break-words dk:text-xs"><?= h((string)$delivery['response']) ?></p>
                        <?php endif; ?>
                    </section>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div data-dk-tab-panel="<?= h($tabScope) ?>:source" hidden class="dk:p-3 dk:sm:p-4">
                    <?php if ($callsite === null) : ?>
                    <p class="dk:text-xs dk:text-zinc-400">Source unavailable</p>
                    <?php else : ?>
                    <?= $this->element('Crustum/DebugBar.ui/source_ref', [
                        'file' => (string)($callsite['file'] ?? ''),
                        'line' => (int)($callsite['line'] ?? 0),
                    ]) ?>
                    <?php if ($frames !== []) : ?>
                    <dl class="dk:mt-3 dk:divide-y dk:divide-zinc-200/90 dk:dark:divide-zinc-800">
                        <?php foreach ($frames as $frame) :
                            $frame = (array)$frame;
                                ?>
                        <div class="dk:grid dk:gap-1 dk:py-2 dk:sm:grid-cols-[8rem_minmax(0,1fr)]">
                            <dt class="dk:min-w-0 dk:break-all dk:text-xs dk:font-semibold dk:text-zinc-500 dk:dark:text-zinc-400"><?= h((string)($frame['function'] ?? '')) ?></dt>
                            <dd class="dk:min-w-0">
                                <?= $this->element('Crustum/DebugBar.ui/source_ref', [
                                    'file' => (string)($frame['file'] ?? ''),
                                    'line' => (int)($frame['line'] ?? 0),
                                ]) ?>
                            </dd>
                        </div>
                        <?php endforeach; ?>
                    </dl>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
    <?php
    $detailBody = (string)ob_get_clean();
        ?>
    <?= $this->element('Crustum/DebugBar.ui/inspector_workspace', [
        'mode' => 'split',
        'frame' => 'top',
        'list' => $this->element('Crustum/DebugBar.ui/inspector_list_panel', [
            'controls' => $controls,
            'body' => $listBody,
        ]),
        'detail' => $detailBody,
    ]) ?>
    <?php endif; ?>
</div>
