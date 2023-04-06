<?php
/*
 * Copyright (c) Jan Sohn / xxAROX
 * All rights reserved.
 * I don't want anyone to use my source code without permission.
 */
declare(strict_types=1);
set_time_limit(0);
ini_set("memory_limit", "-1");
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
$localServerPath = "C:/Users/" . getenv("USERNAME") . "/Desktop/pmmp4"; // string|null
$NAMESPACE = "src/Main";
$packages = [
];
$encode = false;
$enable_version_suffix = false;
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
echo "[INFO]: Updating autoloader" . PHP_EOL;
passthru("composer  --no-dev --no-interaction dump-autoload -o", $result_code);
if ($result_code != 0) throw new ErrorException("Error while updated autoloader.");
$loader = include_once __DIR__ . "/vendor/autoload.php";
$startTime = microtime(true);
$from = getcwd() . DIRECTORY_SEPARATOR;
$description = yaml_parse_file($from . "plugin.yml");
$to = __DIR__ . DIRECTORY_SEPARATOR . "out" . DIRECTORY_SEPARATOR . $description["name"] . DIRECTORY_SEPARATOR;
$outputPath = $from . "out" . DIRECTORY_SEPARATOR . $description["name"] . ($enable_version_suffix
		? "_v" . $description["version"] : "");
echo "[INFO]: Starting.." . PHP_EOL;
@mkdir($to, 0777, true);
cleanDirectory($to);
// include all important files
if (is_dir($from . "src"))
	copyDirectory($from . "src", $to . "src/${NAMESPACE}");
if (is_file($from . "LICENSE"))
	file_put_contents($to . "LICENSE", file_get_contents($from . "LICENSE"));
if (is_file($from . "README.md"))
	file_put_contents($to . "README.md", file_get_contents($from . "README.md"));
if (is_dir($from . "resources"))
	copyDirectory($from . "resources", $to . "resources");
yaml_emit_file($to . "plugin.yml", $description);
// include all packages
foreach ($packages as $vendor => $obj) {
	if (str_ends_with($vendor, "/")) $vendor = substr($vendor, 0, -1);
	foreach ($obj["paths"] as $paths) {
		foreach ($paths as $from2 => $to2) {
			if (is_dir($from . "vendor/$vendor")) copyDirectory($from . "vendor/$vendor/$from2", $to . $to2);
			else throw new RuntimeException("Package '$vendor' is not installed.");
		}
	}
}
echo "[INFO]: Included " . count($packages) . " package" . (count($packages) == 1 ? "" : "s") . PHP_EOL;
// plugin encoder
$excluded = [];
foreach ($packages as $vendor => $obj) {
	if ($obj["encode"] ?? false)
		$excluded[] = $vendor . "/";
}
echo "[INFO]: Encoding plugin.." . PHP_EOL;
if ($encode) {
	echo "[INFO]: Encoding plugin.." . PHP_EOL;
	require_once "vendor/xxarox/plugin-security/src/Encoder.php";
	(new \xxAROX\PluginSecurity\Encoder($to, $excluded))->encode();
	echo "[INFO]: Encoding done!" . PHP_EOL;
	$to = $to . "/output";
}
echo "[INFO]: Encoding done!" . PHP_EOL;
generatePhar($outputPath, $to);
if (!empty($localServerPath) && is_dir($localServerPath . "/plugins")) {
	echo "[INFO]: Compiling.." . PHP_EOL;
	generatePhar($localServerPath . "/plugins/" . $description["name"] . ($enable_version_suffix
			? "_v" . $description["version"] : ""), $to);
}
/**
 * Function copyDirectory
 * @param string $from
 * @param string $to
 * @param array $ignoredFiles
 * @return void
 */
function copyDirectory(string $from, string $to, array $ignoredFiles = []): void{
	@mkdir($to, 0777, true);
	if (is_file($from)) {
		$files = [$from];
	} else {
		$ignoredFiles = array_map(fn(string $path) => str_replace("/", "\\", $path), $ignoredFiles);
		$files = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), function (SplFileInfo $fileInfo, $key, $iterator) use ($from, $ignoredFiles): bool{
			if (!empty($ignoredFiles)) {
				$path = str_replace("/", "\\", $fileInfo->getPathname());
				foreach ($ignoredFiles as $ignoredFile) {
					if (str_starts_with($path, $ignoredFile)) {
						return false;
					}
				}
			}
			return true;
		}), RecursiveIteratorIterator::SELF_FIRST);
	}
	/** @var SplFileInfo $fileInfo */
	foreach ($files as $fileInfo) {
		$target = str_replace($from, $to, $fileInfo->getPathname());
		if ($fileInfo->isDir()) {
			@mkdir($target, 0777, true);
		} else {
			$contents = file_get_contents($fileInfo->getPathname());
			file_put_contents($target, $contents);
		}
	}
}

/**
 * Function cleanDirectory
 * @param string $directory
 * @return void
 */
function cleanDirectory(string $directory): void{
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
	/** @var SplFileInfo $fileInfo */
	foreach ($files as $fileInfo) {
		if ($fileInfo->isDir()) {
			rmdir($fileInfo->getPathname());
		} else {
			unlink($fileInfo->getPathname());
		}
	}
}
/**
 * Function generatePhar
 * @param string $outputPath
 * @param string $to
 * @return void
 */
function generatePhar(string $outputPath, string $to): void{
	echo "[INFO]: Building Phar in '$to'" . PHP_EOL;
	global $startTime;
	@unlink($outputPath . ".phar");
	$phar = new Phar($outputPath . ".phar");
	while (true) {
		try {
			$phar->buildFromDirectory($to);
			break;
		} catch (PharException $e) {
		}
		echo "Cannot access to file, file is used" . PHP_EOL;
		sleep(2);
	}
	$phar->buildFromDirectory($to);
	$phar->addFromString("C:/.lock", "This cause the devtools extract error");
	$phar->setSignatureAlgorithm(Phar::SHA512, "bdc70a4aeec173d80eae3f853019fda7270f32f78fc2590d7082a888b76365e923efcdcba6117a977c17a76f82c79a6dcbda1dfc097b6380839087a3d54dbb7f");
	$phar->compressFiles(Phar::GZ);
	echo "[INFO]: Built in " . round(microtime(true) - $startTime, 3) . " seconds! Output path: {$outputPath}.phar" . PHP_EOL;
}
