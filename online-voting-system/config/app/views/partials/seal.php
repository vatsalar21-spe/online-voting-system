<?php
/**
 * @var string $sealSize CSS size, e.g. '2.25rem'
 * @var string $sealTone 'brass' | 'navy'
 */
$sealSize = $sealSize ?? '2.25rem';
$sealTone = $sealTone ?? 'brass';
?>
<svg viewBox="0 0 100 100" class="ovs-seal ovs-seal--<?= $sealTone ?>" style="width:<?= $sealSize ?>;height:<?= $sealSize ?>" aria-hidden="true">
    <circle cx="50" cy="50" r="46" class="ovs-seal__ring-outer" />
    <circle cx="50" cy="50" r="37" class="ovs-seal__ring-inner" />
    <?php for ($i = 0; $i < 20; $i++): $angle = $i * (360 / 20); ?>
        <line x1="50" y1="5.5" x2="50" y2="11.5" class="ovs-seal__tick" transform="rotate(<?= $angle ?> 50 50)" />
    <?php endfor; ?>
    <text x="50" y="58" text-anchor="middle" class="ovs-seal__text">OVS</text>
</svg>
