<?php
$page = 'users';
$pageTitle = 'Gestion des utilisateurs';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stats.php';
requireRole('admin');
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$msg = getParam('msg');
$messages = [
    'created' => 'Utilisateur créé avec succès',
    'updated' => 'Utilisateur modifié avec succès',
    'deleted' => 'Utilisateur supprimé',
    'toggled' => 'Statut modifié avec succès',
];

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = postParam('action');

    if ($action == 'create') {
        $data = [
            'nom' => trim(postParam('nom')),
            'prenom' => trim(postParam('prenom')),
            'email' => trim(postParam('email')),
            'mot_de_passe' => postParam('mot_de_passe'),
            'role' => postParam('role'),
            'telephone' => trim(postParam('telephone')),
            'organisation' => trim(postParam('organisation')),
        ];
        $result = registerUser($data);
        if ($result['success']) {
            redirect(APP_URL . "/admin/users.php?msg=created");
        } else {
            $error = $result['error'];
        }
    }

    if ($action == 'delete') {
        $uid = (int) postParam('user_id');
        if ($uid != $current_user['id']) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$uid]);
            redirect(APP_URL . "/admin/users.php?msg=deleted");
        }
    }

    if ($action == 'toggle') {
        $uid = (int) postParam('user_id');
        $stmt = $db->prepare("UPDATE users SET actif = 1 - actif WHERE id = ?");
        $stmt->execute([$uid]);
        redirect(APP_URL . "/admin/users.php?msg=toggled");
    }
}

$users = $db->query("SELECT * FROM users ORDER BY date_inscription DESC")->fetchAll();
?>

<div class="page-header">
    <div>
        <h1>Gestion des utilisateurs</h1>
        <p>Trois acteurs : Administrateur, Chercheur, Répondant</p>
    </div>
    <button onclick="document.getElementById('modal-user').classList.add('show')" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Nouvel utilisateur
    </button>
</div>

<?php if ($msg && isset($messages[$msg])): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <div><?= $messages[$msg] ?></div>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i>
    <div><?= e($error) ?></div>
</div>
<?php endif; ?>

<!-- Statistiques par rôle -->
<div class="grid grid-3 mb-6">
    <?php
    $counts = ['admin' => 0, 'chercheur' => 0, 'repondant' => 0];
    foreach ($users as $u) $counts[$u['role']]++;
    ?>
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--danger-50); color: var(--danger);"><i class="fas fa-user-shield"></i></div>
        <div class="stat-info">
            <h3><?= $counts['admin'] ?></h3>
            <p>Administrateurs</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info">
            <h3><?= $counts['chercheur'] ?></h3>
            <p>Chercheurs</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-user"></i></div>
        <div class="stat-info">
            <h3><?= $counts['repondant'] ?></h3>
            <p>Répondants</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Liste des utilisateurs (<?= count($users) ?>)</h3>
    </div>
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Organisation</th>
                    <th>Statut</th>
                    <th>Inscription</th>
                    <th>Dernière connexion</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td class="font-semibold">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 12px; flex-shrink: 0;">
                                <?= strtoupper(substr($u['prenom'], 0, 1) . substr($u['nom'], 0, 1)) ?>
                            </div>
                            <?= e($u['prenom'] . ' ' . $u['nom']) ?>
                        </div>
                    </td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge badge-<?= roleColor($u['role']) ?>"><?= roleLabel($u['role']) ?></span></td>
                    <td><?= e($u['organisation'] ?: '-') ?></td>
                    <td>
                        <?php if ($u['actif']): ?>
                            <span class="badge badge-success"><i class="fas fa-check"></i> Actif</span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><i class="fas fa-pause"></i> Inactif</span>
                        <?php endif; ?>
                    </td>
                    <td><?= formatDate($u['date_inscription'], 'd/m/Y') ?></td>
                    <td><?= $u['derniere_connexion'] ? formatDate($u['derniere_connexion'], 'd/m/Y H:i') : '-' ?></td>
                    <td>
                        <?php if ($u['id'] != $current_user['id']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-outline btn-sm" title="<?= $u['actif'] ? 'Désactiver' : 'Activer' ?>">
                                <i class="fas fa-<?= $u['actif'] ? 'pause' : 'play' ?>"></i>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm confirm-delete" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="badge badge-primary">Vous</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Nouvel utilisateur -->
<div class="modal-overlay" id="modal-user">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus text-primary"></i> Nouvel utilisateur</h3>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('show')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom <span class="required">*</span></label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Mot de passe <span class="required">*</span></label>
                        <input type="password" name="mot_de_passe" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rôle <span class="required">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="repondant">Répondant</option>
                            <option value="chercheur">Chercheur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="telephone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Organisation</label>
                        <input type="text" name="organisation" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal-overlay').classList.remove('show')">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
