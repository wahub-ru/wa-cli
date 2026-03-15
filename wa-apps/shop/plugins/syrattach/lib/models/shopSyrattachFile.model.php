<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright (c) 2014-2022, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */

declare(strict_types=1);

/**
 * Class shopSyrattachFileModel
 */
class shopSyrattachFileModel extends waModel
{
    protected $table = 'shop_syrattach_files';

    /**
     * Adds a new record to the database
     * If a file with the same name and extension exists the new name will
     * be given %name%_%counter%.%ext%, i,e if file.pdf exists, the
     * newly uploaded file with the same name will be renamed to file_1.ext
     *
     * @param int $product_id
     * @param waRequestFile $file
     * @param bool $copy
     * @return array
     * @throws waException
     */
    public function add(int $product_id, waRequestFile $file, bool $copy = false): array
    {
        if (!$product_id) {
            throw new waException(_wp("Product ID missing while file metadata saving"));
        }

        $target_dir = shopSyrattachPlugin::getDirectory($product_id);

        $this->checkDirectory($target_dir);

        $data = array(
            'product_id'      => $product_id,
            'name'            => $this->getUniqueFileName($file, $target_dir),
            'sort'            => $this->getSortValue($product_id),
            'upload_datetime' => date("Y-m-d H:i:s"),
            'size'            => $file->size,
            'ext'             => $file->extension
        );

        $data['id'] = $this->insert($data);

        if (!$data['id']) {
            throw new waException(_w('Database error'));
        }

        if (!$copy) {
            $file->moveTo($target_dir, $data['name']);
        } else {
            $file->copyTo($target_dir, $data['name']);
        }

        $data['url'] = shopSyrattachPlugin::getFileUrl($data);

        return $data;
    }

    /**
     *
     * @param $dir string
     * @throws waException
     */
    private function checkDirectory(string $dir)
    {
        if ((file_exists($dir) && !is_writable($dir)) || (!file_exists($dir) && !waFiles::create($dir, true))) {
            throw new waException("Error saving file. Check write permissions.");
        }
    }

    /**
     *
     * @param waRequestFile $file
     * @param $path
     * @return string
     * @internal param int $product_id
     */
    private function getUniqueFileName(waRequestFile $file, $path): string
    {
        if (!file_exists($path . '/' . $file->name)) return $file->name;

        $i = 1;
        $pathinfo = pathinfo($file->name);
        do {
            $name = sprintf('%s_%d', $pathinfo['filename'], $i++);
            $filename = $name . "." . $pathinfo['extension'];
        } while (file_exists($path . DIRECTORY_SEPARATOR . $filename) && is_file($path . DIRECTORY_SEPARATOR . $filename));

        return $filename;
    }

    /**
     *
     * @param int $product_id
     * @return int
     * @throws waException
     */
    private function getSortValue(int $product_id): int
    {

        $info = $this->select('MAX(`sort`)+1 AS `max`, COUNT(1) AS `cnt`')
            ->where($this->getWhereByField('product_id', $product_id))
            ->fetch();

        if ($info['cnt']) {
            return (int)$info['max'];
        }

        return 0;
    }

    /**
     *
     * @param int|string $product_id
     * @param bool $file_urls
     * @return array
     * @throws waException
     */
    public function getByProductId($product_id, bool $file_urls = false): array
    {
        $attachments = $this->select("*")
            ->where("product_id=i:product_id", array('product_id' => $product_id))
            ->order("sort ASC")
            ->fetchAll();

        foreach ($attachments as $key => $attachment) {
            $attachments[$key]['id'] = (int)$attachment['id'];
            $attachments[$key]['sort'] = (int)$attachment['sort'];
            $attachments[$key]['size'] = (int)$attachment['size'];
            $attachments[$key]['product_id'] = (int)$attachment['product_id'];
            if ($file_urls) $attachments[$key]['url'] = shopSyrattachPlugin::getFileUrl($attachment);
        }

        return $attachments;
    }

    /**
     * Deletes record and attached file
     *
     * @param int|string $id
     * @param bool $delete_file
     * @throws Exception
     * @throws waException
     */
    public function delete($id, bool $delete_file = true)
    {
        $attachment = $this->getById($id);

        if (!$attachment) {
            throw new waException(sprintf_wp("Cannot find a record for attachment ID#%d", $id));
        }

        $file = shopProduct::getPath(
            $attachment['product_id'],
            shopSyrattachPlugin::SYRATTACH_ATTACHMENTS_FOLDER . DIRECTORY_SEPARATOR . $attachment['name'],
            true);

        if (wa()->getConfig()->isDebug()) {
            waLog::log(sprintf_wp("Try to delete '%s'", $file), shopSyrattachPlugin::LOG);
        }

        if (!$this->deleteById($id)) {
            throw new waException(_wp("Delete error"));
        }

        try {
            if ($delete_file) {
                waFiles::delete($file);
            }
        } catch (waException $e) {
            waLog::log(
                sprintf_wp("SyrAttach Plugin cannot delete file %s. Message: %s", $file, $e->getMessage()),
                shopSyrattachPlugin::LOG
            );
        }
    }
}
