<?php $__env->startSection('title', 'Comanda #'.$comanda->numero_progressivo); ?>

<?php $__env->startSection('content'); ?>
<?php
    $tutte = $righe;
    $cucina = $righe->filter(fn ($r) => $r['area_stampa'] === 'cucina');
    $griglia = $righe->filter(fn ($r) => $r['area_stampa'] === 'griglia');
    $metodo = $comanda->metodo_pagamento;
?>

<div class="no-print" style="padding:1rem;text-align:center">
    <button class="btn btn-primary" onclick="window.print()">Stampa</button>
    <a class="btn" href="<?php echo e(route('cassa', absolute: false)); ?>">Torna alla cassa</a>
    <p>Comanda #<?php echo e($comanda->numero_progressivo); ?> — <?php echo e(number_format($comanda->totale, 2, ',', '.')); ?> €</p>
</div>

<div class="print-sheet">
    
    <section class="tag-cliente">
        <div class="tag-head">
            <div>
                <div><?php echo e($impostazioni->intestazione_nome); ?> <?php echo e($impostazioni->intestazione_anno); ?></div>
                <div class="meta-small">CLIENTE</div>
            </div>
            <div class="tag-num">#<?php echo e($comanda->numero_progressivo); ?></div>
        </div>
        <div class="meta-small"><?php echo e($comanda->serata->data->format('d/m/Y')); ?> · <?php echo e($comanda->created_at->format('H:i')); ?></div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $tutte; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="tag-line">
                <strong><?php echo e($r['quantita']); ?></strong>
                <span><?php echo e($r['nome']); ?></span>
                <span><?php echo e(number_format($r['importo'], 2, ',', '.')); ?></span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="totale-print">
            TOTALE PAGATO: <?php echo e(number_format($comanda->totale, 2, ',', '.')); ?> €
        </div>
        <div class="pay-badge <?php echo e($metodo); ?>">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($metodo === 'contante'): ?>
                € CONTANTE
            <?php elseif($metodo === 'pos'): ?>
                ▭ POS
            <?php else: ?>
                MISTO
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </section>

    <div class="tag-right">
        <div class="tag-top">
            
            <section class="tag-cucina">
                <div class="tag-head">
                    <span>CUCINA</span>
                    <span class="tag-num">#<?php echo e($comanda->numero_progressivo); ?></span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $cucina; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="tag-line-check">
                        <span class="check-box"></span>
                        <span class="dotted"><strong><?php echo e($r['quantita']); ?></strong> <?php echo e($r['nome']); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="meta-small">— nessuna voce —</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="campo-mano">
                    Cameriere
                    <div class="linea"></div>
                </div>
            </section>

            
            <section class="tag-cameriere">
                <div class="tag-head">
                    <span>CAMERIERE</span>
                    <span class="tag-num">#<?php echo e($comanda->numero_progressivo); ?></span>
                </div>
                <div class="campo-mano" style="margin-top:0;border-top:0;padding-top:0">
                    Tavolo
                    <div class="linea"></div>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $tutte; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="tag-line-check">
                        <span class="check-box"></span>
                        <span class="dotted"><strong><?php echo e($r['quantita']); ?></strong> <?php echo e($r['nome']); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </section>
        </div>

        
        <section class="tag-griglia">
            <div class="tag-head">
                <span>GRIGLIA</span>
                <span style="flex:1;margin:0 4mm;border-bottom:1px solid #000;font-weight:400;font-size:.85em;padding-left:2mm">Cameriere</span>
                <span class="tag-num">#<?php echo e($comanda->numero_progressivo); ?></span>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $griglia; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="tag-line-check">
                    <span class="check-box"></span>
                    <span class="dotted"><strong><?php echo e($r['quantita']); ?></strong> <?php echo e($r['nome']); ?></span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="meta-small">— nessuna voce —</div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </section>
    </div>
</div>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($autoPrint): ?>
<?php $__env->startPush('scripts'); ?>
<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 200);
});
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.print', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /workspace/resources/views/print/comanda.blade.php ENDPATH**/ ?>