<?php
function size_format($size, $precision = 2) {
	$base = log($size, 1024);
	$suffix = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'][floor($base)];

	return
		round(pow(1024, $base - floor($base)), $precision).' '.$suffix;
}

enum Show {
	case DIR;
	case FILE;
	case BLOB;
	case INVALID;
}

enum Kind {
	case IMAGE;
	case ANY;
}

if (! isset($_GET['path']))
	$_GET['path'] = '.';

$path = realpath(html_entity_decode($_GET['path']));

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
	$fmt = 'l j F Y h:i:s A';

	$atime = date($fmt, $stat['atime']);
	$mtime = date($fmt, $stat['mtime']);
	$ctime = date($fmt, $stat['ctime']);

	return <<<"EOT"
		<h2><em>Last access:</em> $atime</h2>
		<h2><em>Last modification:</em> $mtime</h2>
		<h2><em>Last change:</em> $ctime</h2>
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

		<h1><em>Folder:</em> <?= basename($path) ?>/</h1>
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
		<h1><em>File:</em> <?= basename($path) ?></h1>
		<h2><em>Size:</em> <?= size_format(filesize($path)) ?></h2>
		<?= $time() ?>
		<?= $button('back to '.basename(dirname($path)).'/') ?>
	</header>

	<main>
		<pre><?= htmlspecialchars(file_get_contents($path)) ?></pre>
	</main>

<?php elseif ($show == Show::BLOB): ?>

	<header>
		<h1><em>Blob:</em> <?= basename($path) ?> </h1>
		<h2><em>Size:</em> <?= size_format(filesize($path)) ?></h2>
		<h2><em>Mime type:</em> <?= $mime ?> </h1>
		<?= $time() ?>
		<?= $button('back to '.basename(dirname($path)).'/') ?>
	</header>

	<main>
		<?php
		$kind = explode('/', $mime);

		if ($kind[0] == 'image') {
			$img = base64_encode(file_get_contents($path));
			$fmt = $kind[1];
			echo <<<"EOT"
			<img src="data:image/$fmt;base64, $img"></img>
			EOT;
		}
		?>
		<?php $data = file_get_contents($path); ?>

		<div class="blob-items">
			<div>
				<h3>text</h3>
				<pre><?= preg_replace('/[^[:print:]]/', '.', $data); ?></pre>
			</div>

			<div>
				<h3>bytes</h3>
				<pre><?=
				implode(
					' ',
					array_map(
						fn($b) => sprintf('%02X', $b),
						unpack('C*', $data)
					)
				)
				?></pre>
			</div>
		</div>
	</main>

<?php endif ?>

	<footer>
		<p><?= $path == '.' ? __DIR__ : $path ?></p>
	</footer>

	</form>
</body>
</html>
