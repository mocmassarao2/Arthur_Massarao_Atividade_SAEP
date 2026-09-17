<?php
require_once 'config.php';

$tabelas = [
    'eleitor' => ['nome' => 'Eleitor', 'tabela' => 'eleitor', 'id' => 'id_eleitor', 'campos' => ['nome', 'numero_titulo', 'cidade']],
    'candidato' => ['nome' => 'Candidato', 'tabela' => 'candidato', 'id' => 'id_candidato', 'campos' => ['nome', 'numero_candidato', 'cargo']]
];

function limpar($v) { return htmlspecialchars($v ?? ''); }

$msg = '';
$editar = null;
$tipoGet = $_GET['tipo'] ?? null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tipo = $_POST['tipo'];
        $d = $tabelas[$tipo];
        $idCampo = $d['id'];
        $acao = $_POST['acao'];

        if ($acao === 'excluir') {
            $conn->prepare("DELETE FROM {$d['tabela']} WHERE $idCampo = ?")->execute([$_POST['id']]);
            $msg = 'Registro excluído.';
        } else {
            $valores = array_map(fn($c) => $_POST[$c], $d['campos']);
            
            if ($acao === 'editar') {
                $set = implode(', ', array_map(fn($c) => "$c = ?", $d['campos']));
                $valores[] = $_POST['id'];
                $conn->prepare("UPDATE {$d['tabela']} SET $set WHERE $idCampo = ?")->execute($valores);
                $msg = 'Registro atualizado.';
            } else {
                $campos = implode(', ', $d['campos']);
                $marks = implode(', ', array_fill(0, count($valores), '?'));
                $conn->prepare("INSERT INTO {$d['tabela']} ($campos) VALUES ($marks)")->execute($valores);
                $msg = 'Registro cadastrado.';
            }
        }
    }

    if (isset($tipoGet, $_GET['id'])) {
        $stmt = $conn->prepare("SELECT * FROM {$tabelas[$tipoGet]['tabela']} WHERE {$tabelas[$tipoGet]['id']} = ?");
        $stmt->execute([$_GET['id']]);
        $editar = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $msg = 'Erro: ' . $e->getMessage();
}
?>

<h1>CRUD Eleitoral</h1>
<p><?= limpar($msg) ?></p>

<?php foreach ($tabelas as $tipo => $d): $isEdit = ($editar && $tipoGet === $tipo); ?>

    <h2><?= $isEdit ? 'Editar' : 'Cadastrar' ?> <?= $d['nome'] ?></h2>

    <form method="post">
        <input type="hidden" name="tipo" value="<?= $tipo ?>">
        <input type="hidden" name="acao" value="<?= $isEdit ? 'editar' : 'cadastrar' ?>">
        
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= limpar($editar[$d['id']]) ?>">
        <?php endif; ?>

        <?php foreach ($d['campos'] as $c): ?>
            <label><?= ucfirst(str_replace('_', ' ', $c)) ?>:</label>
            <input type="text" name="<?= $c ?>" required value="<?= limpar($isEdit ? $editar[$c] : '') ?>">
        <?php endforeach; ?>

        <button type="submit"><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
        <?php if ($isEdit): ?><a href="index.php">Cancelar</a><?php endif; ?>
    </form>

    <h2>Lista de <?= $d['nome'] ?>s</h2>
    <ul>
        <?php foreach ($conn->query("SELECT * FROM {$d['tabela']}")->fetchAll(PDO::FETCH_ASSOC) as $r): ?>
            <li>
                <strong>ID:</strong> <?= limpar($r[$d['id']]) ?>
                <?php foreach ($d['campos'] as $c): ?> | <?= limpar($r[$c]) ?><?php endforeach; ?>
                
                <a href="?tipo=<?= $tipo ?>&id=<?= $r[$d['id']] ?>">Editar</a>
                
                <form method="post" style="display:inline">
                    <input type="hidden" name="tipo" value="<?= $tipo ?>">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= $r[$d['id']] ?>">
                    <button type="submit">Excluir</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>

<?php endforeach; ?>