<?php

namespace App\Controller\Media;

use App\Controller\ConnecTAController;
use App\Entity\Media\Comment;
use App\Entity\Media\Gallery;
use App\Entity\Media\Photo;
use App\Model\Permission;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class GalleryController extends ConnecTAController
{

    CONST THUMB_FOLDER = "storage/photos/thumb";
    CONST HD_FOLDER = "storage/photos/hd";
    const ORIGIN_FOLDER = "storage/photos/origin";

    /**
     * @Route("/media/gallery/list", methods="GET")
     */
    public function getList(Request $request)
    {
        if (null === $this->getUser()) {
            $canViewPrivate = false;
            $canViewAll = false;
        } else {
            $canViewPrivate = $this->getUser()->hasRole(Permission::IS_AUTHENTICATED);
            $canViewAll = $this->getUser()->hasRole(Permission::IS_ADMIN);
        }

        $repo = $this->getDoctrine()->getManager()->getRepository(Gallery::class);
        return $this->response()->responseEntity($repo->getList($canViewPrivate, $canViewAll));
    }

    /**
     * @Route("/media/gallery/detail", methods="GET")
     */
    public function getDetail(Request $request)
    {
        if (null === $this->getUser()) {
            $canViewPrivate = false;
            $canViewAll = false;
        } else {
            $canViewPrivate = $this->getUser()->hasRole(Permission::IS_AUTHENTICATED);
            $canViewAll = $this->getUser()->hasRole(Permission::IS_ADMIN);
        }

        $repo = $this->getDoctrine()->getManager()->getRepository(Gallery::class);
        return $this->response()->responseRawEntity($repo->getGallery($request->query->get("id"), $canViewPrivate, $canViewAll));
    }

    /**
     * @Route("/media/gallery/comment", methods="POST")
     */
    public function comment(Request $request, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $this->denyAccessUnlessGranted(Permission::IS_AUTHENTICATED);
        $content = $request->request->get("content");
        $repo = $this->getDoctrine()->getManager()->getRepository(Gallery::class);
        $comment = new Comment();
        $comment->setContent($content);
        if(is_null($comment->getContent()))
            return $this->response()->response($translator->trans("empty-comment"),Response::HTTP_FORBIDDEN);
        $comment->setPostUser($this->getUser());
        $comment->setGallery($repo->find($request->request->get("id")));
        $em = $this->getDoctrine()->getManager();
        $em->persist($comment);
        $em->flush();
        return $this->response()->response(null);
    }

    /**
     * @Route("/media/gallery/like", methods="POST")
     */
    public function like(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $repo = $this->getDoctrine()->getManager()->getRepository(Gallery::class);
        $gallery = $repo->getGallery($request->request->get("id"));
        $gallery->like($this->getUser());
        $em = $this->getDoctrine()->getManager();
        $em->persist($this->getUser());
        $em->flush();
        return $this->response()->response(null);
    }

    /**
     * @Route("/media/gallery/like", methods="GET")
     */
    public function likeStatus(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $repo = $this->getDoctrine()->getManager()->getRepository(Gallery::class);
        $gallery = $repo->getGallery($request->query->get("id"));
        if(is_null($gallery))
            throw $this->createAccessDeniedException();
        return $this->response()->response($gallery->likeStatus($this->getUser()));
    }

    /**
     * @Route("/admin/media/comment", methods="GET")
     */
    public function commentPage()
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        return $this->render("admin/media/comment.html.twig");
    }

    /**
     * @Route("/admin/media/upload", methods="GET")
     */
    public function uploadPage()
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        return $this->render("admin/media/upload.html.twig");
    }

    /**
     * @Route("/admin/media/photo", methods="GET")
     */
    public function managePhotos(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        if ($request->query->has("gallery_id"))
            return $this->render("admin/media/photo.php.twig", ["gallery_id" => $request->query->get("gallery_id")]);
        else
            return $this->render("admin/media/photo.php.twig", ["gallery_id" => 0]);
    }

    /**
     * @Route("/admin/media/gallery", methods="GET")
     */
    public function manageGallery()
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        return $this->render("admin/media/gallery.html.twig");
    }

    /**
     * @Route("/admin/media/comment/edit", methods="POST")
     */
    public function editComment(Request $request)
    {
        $page = $request->request->get("page") ?? 1;
        $rows = $request->request->get("rows") ?? 10;
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Comment::class);
        if ($request->request->has("delete")) {
            $items = json_decode($request->request->get("delete"), true);
            foreach ($items as $item) {
                $em->remove($repo->findOneBy(["id" => $item]));
            }
            $em->flush();
        }
        return $this->response()->responseRowEntity($repo->getList($page, $rows), $repo->getCount(), Response::HTTP_OK);
    }

    /**
     * @Route("/admin/media/photo/delete")
     */
    public function delete(Request $request){
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $url = $request->query->get("id") ?? "";
        $index = strripos($url,"/") + 1;
        $file = substr($url,$index);
        $repo = $this->getDoctrine()->getManager()->getRepository(Photo::class);
        $photo = $repo->getPhotoByFile($file);
        if($request->getMethod() === "GET"){
            return $this->render("admin/media/delete.html.twig",["file"=>$file,"url"=>$url]);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($photo->remove());
        $em->flush();
        $em->remove($photo);
        $em->flush();
        return $this->render("admin/media/deleted.html.twig");

    }
    /**
     * @Route("/admin/media/gallery/edit", methods="POST")
     */
    public function editGallery(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Gallery::class);
        $photoRepo = $em->getRepository(Photo::class);
        if ($request->request->has("id")) {
            $gallery = $repo->getGallery($request->request->get("id"), true, true) ?? new Gallery();
            if ($request->request->get("delete") == "true") {
                //var_dump($gallery->getPhotos());
                $em->remove($gallery->getCover() ?? new Photo());
                foreach ($gallery->getPhotos() as $photo) {
                    $photo->setGallery(null);
                    $em->persist($photo);
                    $em->remove($photo);
                }
                foreach ($gallery->getComments() as $comment) {
                    $comment->setGallery(null);
                    $em->persist($comment);
                    $em->remove($comment);
                }
                $gallery->removeAllComments();
                $gallery->removeAllPhotos();
                $gallery->setCover(null);
                $em->flush();
                $em->remove($gallery);
            } else {
                $title = $request->request->get("title") ?? "";
                $description = $request->request->get("description") ?? "";
                $priority = $request->request->getInt("priority",1);
                $photo = $photoRepo->getPhoto($request->request->get("cover"));
                $gallery->setCover($photo);
                $gallery->setTitle($title);
                $gallery->setPriority($priority);
                $gallery->setDescription($description);
                $gallery->setIsPublic($request->request->get("public") == "true");
                $gallery->setIsVisible($request->request->get("visible") == "true");
                $em->persist($gallery);
            }
            $em->flush();
        }
        $list = $repo->getAllList();
        return $this->response()->responseEntity($list, 200);
    }

    /**
     * @Route("/admin/media/photo/edit", methods="POST")
     */
    public function editPhotos(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $em = $this->getDoctrine()->getManager();
        $photoRepo = $em->getRepository(Photo::class);
        $galleryRepo = $em->getRepository(Gallery::class);
        $page = $request->request->get("page") ?? 1;
        $rows = $request->request->get("rows") ?? 10;
        $filter = $request->query->get("filter");
        $content = json_decode($request->getContent(), true);
        if (@!is_null($content)) {
            foreach ($content["id"] as $id) {
                $photo = $photoRepo->getPhoto($id);
                if (isset($content["delete"])) {
                    $em->remove($photo);
                } else {
                    $gallery = $galleryRepo->getGallery($content["gallery"]);
                    $photo->setGallery($gallery);
                    $photo->setIsPublic($content["public"]);
                    $photo->setIsVisible($content["visible"]);
                    $em->persist($photo);
                }

            }
            $em->flush();
        }
        if ($filter == "all") {
            $showAll = true;
            $name = null;
        } else {
            $showAll = false;
            if ($filter == "null") {
                $name = null;
            } else {
                $name = $filter;
            }
        }
        $repo = $this->getDoctrine()->getRepository(Photo::class);
        $list = $repo->getList($page, $rows, $showAll, $name);
        $count = $repo->getListCount($page, $rows, $showAll, $name);
        return $this->response()->responseRowEntity($list, $count, 200);
    }

    /**
     * @Route("media/gallery/upload", methods="POST")
     */
    publiC function addPhoto(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $allowOrigin = $request->request->get("allowOrigin");
        $originalPhoto = $request->files->get("photo");
        $path = $originalPhoto->getRealPath();

        $thumb = new \Imagick($path);
        $hd = new \Imagick($path);

        $thumb->thumbnailImage(300, 300, true);
        $hd->resizeImage(2048, 2048, \Imagick::FILTER_LANCZOS, 1, true);

        $thumb->setImageFormat("png");
        $hd->setImageFormat("png");


        $thumb->writeImage($path . ".thumb.png");
        $hd->writeImage($path . ".hd.png");

        exec("cwebp -q 80 " . $path . ".thumb.png -o " . $path . ".thumb.webp ");
        exec("cwebp -q 90 " . $path . ".hd.png -o " . $path . ".hd.webp ");

        $thumbPhoto = new File($path . ".thumb.webp");
        $hdPhoto = new File($path . ".hd.webp");

        unlink($path . ".thumb.png");
        unlink($path . ".hd.png");

        $thumbName = bin2hex(random_bytes(64)) . ".webp";
        $hdName = bin2hex(random_bytes(64)) . ".webp";

        $thumbPhoto->move(self::THUMB_FOLDER, $thumbName);
        $hdPhoto->move(self::HD_FOLDER, $hdName);

        $photo = new Photo();
        $photo->setHd($hdName);
        $photo->setThumb($thumbName);

        $photo->setHeight($hd->getImageHeight());
        $photo->setWidth($hd->getImageWidth());

        if ($allowOrigin == "true") {
            exec("cwebp -q 100 " . $path . " -o " . $path . ".webp ");
            $originName = bin2hex(random_bytes(64)) . ".webp";
            $originPhoto = new File($path . ".webp");
            $originPhoto->move(self::ORIGIN_FOLDER, $originName);
            $photo->setOrigin($originName);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($photo);
        $em->flush();

        return $this->response()->response(array(
            "hd" => $hdName,
            "thumb" => $thumbName
        ), 200);
    }
}
