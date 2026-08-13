<?php
require_once __DIR__ . '/includes/header.php';

$population = (int) postParam('population', 10000);
$marge = (float) postParam('marge', 5);
$confiance = (float) postParam('confiance', 95);
$proportion = (float) postParam('proportion', 50);

$n = calculerTailleEchantillon($population, $marge, $confiance);
?>

<div class="page-header">
    <div>
        <h1>Calcul de la taille d'échantillon</h1>
        <p>Déterminez la taille optimale de votre échantillon</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Paramètres</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Taille de la population</label>
                    <input type="number" name="population" class="form-control" value="<?= $population ?>" min="1">
                    <div class="form-text">Nombre total d'individus dans la population étudiée</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Marge d'erreur (%)</label>
                    <input type="number" name="marge" class="form-control" value="<?= $marge ?>" min="1" max="20" step="0.5">
                    <div class="form-text">Précision souhaitée (typiquement 5%)</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Niveau de confiance (%)</label>
                    <select name="confiance" class="form-control">
                        <option value="90" <?= $confiance == 90 ? 'selected' : '' ?>>90%</option>
                        <option value="95" <?= $confiance == 95 ? 'selected' : '' ?>>95%</option>
                        <option value="99" <?= $confiance == 99 ? 'selected' : '' ?>>99%</option>
                    </select>
                    <div class="form-text">Probabilité que l'intervalle de confiance contienne la vraie valeur</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Proportion estimée (%)</label>
                    <input type="number" name="proportion" class="form-control" value="<?= $proportion ?>" min="1" max="100">
                    <div class="form-text">Utilisez 50% pour une estimation conservatrice</div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-full">
                    <i class="fas fa-calculator"></i> Calculer
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Résultat</h3>
        </div>
        <div class="card-body">
            <div style="text-align: center; padding: 40px 20px;">
                <div style="font-size: 14px; color: var(--gray-500); margin-bottom: 8px;">Taille d'échantillon recommandée</div>
                <div style="font-size: 64px; font-weight: 800; color: var(--primary); letter-spacing: -2px;"><?= $n ?></div>
                <div style="font-size: 16px; color: var(--gray-500); margin-top: 8px;">répondants</div>
            </div>

            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= formatNumber($marge) ?>%</div>
                    <div class="stat-label">Marge d'erreur</div>
                </div>
            </div>
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= formatNumber($confiance) ?>%</div>
                    <div class="stat-label">Niveau de confiance</div>
                </div>
            </div>
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= number_format($population, 0, ',', ' ') ?></div>
                    <div class="stat-label">Population totale</div>
                </div>
            </div>

            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Formule utilisée :</strong><br>
                    n = (Z² × p × (1-p)) / e²<br>
                    avec correction pour population finie.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
