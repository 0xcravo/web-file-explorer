<?php
enum Show {
	case DIR;
	case FILE;
	case BLOB;
	case INVALID;
}

$path = isset($_GET['path']) ?
	realpath(html_entity_decode($_GET['path'])) :
	'.';

$button = function($body) use ($path) {
	$p = dirname($path);

	return <<<"EOT"
		<button
			name="path"
			type="submit"
			value="$p"
			>
			$body
		</button>
	EOT;
};

$mime = mime_content_type($path);
$type = explode('/', $mime)[0];
$stat = stat($path);

$time = function() use ($stat) {
	$fmt = 'l jS F Y h:i:s A';

	$atime = date($fmt, $stat['atime']);
	$mtime = date($fmt, $stat['mtime']);
	$ctime = date($fmt, $stat['ctime']);

	return <<<"EOT"
		<h2>Last access: $atime</h2>
		<h2>Last modification: $mtime</h2>
		<h2>Last change: $ctime</h2>
	EOT;
};

$show = Show::INVALID;

if (is_dir($path))
	$show = Show::DIR;
else if ($type == 'text' and filesize($path) <= 1024 * 10)
	$show = Show::FILE;
else if (is_file($path))
	$show = Show::BLOB;

$show == Show::INVALID and die("`$path` is a invalid file to show");

?>

<!DOCTYPE html>
<html>

<head>
	<!-- <meta http-equiv="refresh" content="2"> -->
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="style.css">
	<title>PHP File Explorer at "<?= basename($path) ?>"</title>
</head>

<body>
<form method="get">

<?php if ($show == Show::DIR): ?>
	<header>

		<h1>Reading: <?= basename($path) ?></h1>
		<?= $time() ?>

	</header>

	<main>
		<ul>

			<li><?= $button('..') ?></li>

			<?php $dir = dir($path); ?>
			<?php while ($entry = $dir->read()): ?>

				<?php
					if ($entry[0] == '.')
						continue;
				
					$file = $entry;
					if (is_dir("$path/$entry"))
						$file .= '/';
				?>

				<li>
					<button
						name="path"
						type="submit"
						value="<?= "$path/$file" ?>"
					>
					<?= $file ?>
					</button>
				</li>

			<?php endwhile ?>
			<?= $dir->close(); ?>

		</ul>
	</main>


<?php elseif ($show == Show::FILE): ?>

	<header>
		<h1>File: <?= basename($path) ?></h1>
		<h2>Size: <?= filesize($path) ?></h2>
		<?= $time() ?>
		<?= $button('back to '.basename(dirname($path)).'/') ?>
	</header>

	<main>
		<pre><?= htmlspecialchars(file_get_contents($path)) ?></pre>
	</main>

<?php elseif ($show == Show::BLOB): ?>

	<header>
		<h1>Blob: <?= basename($path) ?> </h1>
		<h2>Size: <?= filesize($path) ?></h2>
		<h2>Mime type: <?= $mime ?> </h1>
		<?= $time() ?>
		<?= $button('back to '.basename(dirname($path)).'/') ?>
	<header>

	<main>
		<?php $data = file_get_contents($path); ?>

		<h3>text</h3>
		<?php $text = preg_replace('/[^[:print:]]/', '.', $data); ?>
		<pre><?= $text ?></pre>

		<h3>bytes</h3>
		<?php $bytes = unpack('C*', $data); ?>
		<pre><?php foreach ($bytes as $byte) echo $byte.' '; ?></pre>
	</main>

<?php endif ?>

</form>
</body>
</html>
