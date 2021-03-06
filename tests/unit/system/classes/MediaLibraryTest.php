<?php

use System\Classes\MediaLibrary;

class MediaLibraryTest extends TestCase // @codingStandardsIgnoreLine
{
    protected function tearDown()
    {
        $this->removeMedia();
        parent::tearDown();
    }

    public function invalidPathsProvider()
    {
        return [
            ['./file'],
            ['../secret'],
            ['.../secret'],
            ['/../secret'],
            ['/.../secret'],
            ['/secret/..'],
            ['file/../secret'],
            ['file/..'],
            ['......./secret'],
            ['./file'],
        ];
    }

    public function validPathsProvider()
    {
        return [
            ['file'],
            ['folder/file'],
            ['/file'],
            ['/folder/file'],
            ['/.file'],
            ['/..file'],
            ['/...file'],
            ['file.ext'],
            ['file..ext'],
            ['file...ext'],
            ['one,two.ext'],
            ['one(two)[].ext'],
            ['one=(two)[].ext'],
            ['one_(two)[].ext'],
            /*
            Example of a unicode-based filename with a single quote
            @see: https://github.com/octobercms/october/pull/4564
            */
            ['BG中国通讯期刊(Blend\'r)创刊号.pdf'],
        ];
    }

    /**
     * @dataProvider invalidPathsProvider
     */
    public function testInvalidPathsOnValidatePath($path)
    {
        $this->expectException('ApplicationException');
        MediaLibrary::validatePath($path);
    }

    /**
     * @dataProvider validPathsProvider
     */
    public function testValidPathsOnValidatePath($path)
    {
        $result = MediaLibrary::validatePath($path);
        $this->assertInternalType('string', $result);
    }

    public function testListFolderContents()
    {
        $this->setUpStorage();
        $this->copyMedia();

        $contents = MediaLibrary::instance()->listFolderContents();
        $this->assertNotEmpty($contents, 'Media library item is not discovered');

        $item = reset($contents);
        $this->assertAttributeEquals('file', 'type', $item, 'Media library item does not have the right type');
        $this->assertAttributeEquals('/text.txt', 'path', $item, 'Media library item does not have the right path');
        $this->assertAttributeNotEmpty('lastModified', $item, 'Media library item last modified is empty');
        $this->assertAttributeNotEmpty('size', $item, 'Media library item size is empty');
    }

    protected function setUpStorage()
    {
        $this->app->useStoragePath(base_path('storage/temp'));

        config(['filesystems.disks.test_local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ]]);

        config(['cms.storage.media' => [
            'disk'   => 'test_local',
            'folder' => 'media',
            'path'   => '/storage/app/media',
        ]]);
    }

    protected function copyMedia()
    {
        $mediaPath = storage_path('app/media');

        if (!is_dir($mediaPath)) {
            mkdir($mediaPath, 0777, true);
        }

        foreach (glob(base_path('tests/fixtures/media/*')) as $file) {
            $path = pathinfo($file);
            copy($file, $mediaPath . DIRECTORY_SEPARATOR . $path['basename']);
        }
    }

    protected function removeMedia()
    {
        if ($this->app->storagePath() !== base_path('storage/temp')) {
            return;
        }

        foreach (glob(storage_path('app/media/*')) as $file) {
            unlink($file);
        }

        rmdir(storage_path('app/media'));
        rmdir(storage_path('app'));
    }
}
