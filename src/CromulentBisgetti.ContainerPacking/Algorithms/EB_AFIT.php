<?php

class Layer
{
    public $LayerDim;
    public $LayerEval;
}

class ScrapPad
{
    public $CumX;
    public $CumZ;
    public $Post = null;
    public $Pre = null;
}

class ContainerPackingResult
{
    public function __construct()
    {
        $this->AlgorithmPackingResults = new ArrayObject;
    }
    public $ContainerID;
    public $AlgorithmPackingResults = null;
}

class Container
{
    public function __construct(int $id, float $length, float $width, float $height)
    {
        $this->ID = $id;
        $this->Length = $length;
        $this->Width = $width;
        $this->Height = $height;
        $this->Volume = $length * $width * $height;
    }

    public $ID;
    public $Length;
    public $Width;
    public $Height;
    public $Volume;
}

class Item
{
    public function __construct(int $id, float $dim1, float $dim2, float $dim3, int $quantity)
    {
        $this->ID = $id;
        $this->Dim1 = $dim1;
        $this->Dim2 = $dim2;
        $this->Dim3 = $dim3;
        $this->volume = $dim1 * $dim2 * $dim3;
        $this->Quantity = $quantity;
    }

    public $ID;
    public $IsPacked;
    public $Dim1;
    public $Dim2;
    public $Dim3;
    public $CoordX;
    public $CoordY;
    public $CoordZ;
    public $Quantity;
    public $PackDimX;
    public $PackDimY;
    public $PackDimZ;
    public $Volume;
}

class AlgorithmPackingResult
{
    public function __construct()
    {
        $this->PackedItems = new ArrayObject;
        $this->UnpackedItems = new ArrayObject;
    }

    public $AlgorithmID;
    public $AlgorithmName;
    public $IsCompletePack;
    public /*array*/ $PackedItems = null;
    public $PackTimeInMilliseconds;
    public $PercentContainerVolumePacked;
    public $PercentItemVolumePacked;
    public /*array*/ $UnpackedItems = null;
}

interface IPackingAlgorithm
{
    const AL_EB_AFIT = 1;
    // AlgorithmPackingResult
    public function Run(Container $container, ArrayObject $items);
}

class EB_AFIT implements IPackingAlgorithm
{

    public function Run(Container $container, ArrayObject $items)
    {
        $this->Initialize($container, $items);
        $this->ExecuteIterations($container);
        $this->Report($container);

        $this->result = new AlgorithmPackingResult();
        $this->result->AlgorithmID = self::AL_EB_AFIT;
        $this->result->AlgorithmName = "EB-AFIT";

        for ($i = 1; $i <= $this->itemsToPackCount; $i++)
        {
            $this->itemsToPack[$i]->Quantity = 1;

            if (!$this->itemsToPack[$i]->IsPacked)
            {
                $this->result->UnpackedItems->append($this->itemsToPack[$i]);
            }
        }

        $this->result->PackedItems = $this->itemsPackedInOrder;



        if ($this->result->UnpackedItems->count() == 0)
        {
            $this->result->IsCompletePack = true;
        }

        return $this->result;
    }

    private $itemsToPack = new ArrayObject;
    private $itemsPackedInOrder = new ArrayObject;
    private $layers = new ArrayObject;
    private $result;

    private $scrapfirst;
    private $smallestZ;
    private $trash;

    private $evened;
    private $hundredPercentPacked = false;
    private $layerDone;
    private $packing;
    private $packingBest = false;
    private $quit = false;

    private $bboxi;
    private $bestIteration;
    private $bestVariant;
    private $boxi;
    private $cboxi;
    private $layerListLen;
    private $packedItemCount;
    private $x;

    private $bbfx;
    private $bbfy;
    private $bbfz;
    private $bboxx;
    private $bboxy;
    private $bboxz;
    private $bfx;
    private $bfy;
    private $bfz;
    private $boxx;
    private $boxy;
    private $boxz;
    private $cboxx;
    private $cboxy;
    private $cboxz;
    private $layerinlayer;
    private $layerThickness;
    private $lilz;
    private $packedVolume;
    private $packedy;
    private $prelayer;
    private $prepackedy;
    private $preremainpy;
    private $px;
    private $py;
    private $pz;
    private $remainpy;
    private $remainpz;
    private $itemsToPackCount;
    private $totalItemVolume;
    private $totalContainerVolume;

    private function AnalyzeBox(float $hmx, float $hy, float $hmy, float $hz, float $hmz, float $dim1, float $dim2, float $dim3)
    {
        if ($dim1 <= $hmx && $dim2 <= $hmy && $dim3 <= $hmz)
        {
            if ($dim2 <= $hy)
            {
                if ($hy - $dim2 < $this->bfy)
                {
                    $this->boxx = $dim1;
                    $this->boxy = $dim2;
                    $this->boxz = $dim3;
                    $this->bfx = $hmx - $dim1;
                    $this->bfy = $hy - $dim2;
                    $this->bfz = abs($hz - $dim3);
                    $this->boxi = $this->x;
                }
                else if ($hy - $dim2 == $this->bfy && $hmx - $dim1 < $this->bfx)
                {
                    $this->boxx = $dim1;
                    $this->boxy = $dim2;
                    $this->boxz = $dim3;
                    $this->bfx = $hmx - $dim1;
                    $this->bfy = $hy - $dim2;
                    $this->bfz = abs($hz - $dim3);
                    $this->boxi = $this->x;
                }
                else if ($hy - $dim2 == $this->bfy && $hmx - $dim1 == $this->bfx && abs($hz - $dim3) < $this->bfz)
                {
                    $this->boxx = $dim1;
                    $this->boxy = $dim2;
                    $this->boxz = $dim3;
                    $this->bfx = $hmx - $dim1;
                    $this->bfy = $hy - $dim2;
                    $this->bfz = abs($hz - $dim3);
                    $this->boxi = $this->x;
                }
            }
            else
            {
                if ($dim2 - $hy < $this->bbfy)
                {
                    $this->bboxx = $dim1;
                    $this->bboxy = $dim2;
                    $this->bboxz = $dim3;
                    $this->bbfx = $hmx - $dim1;
                    $this->bbfy = $dim2 - $hy;
                    $this->bbfz = abs($hz - $dim3);
                    $this->bboxi = $this->x;
                }
                else if ($dim2 - $hy == $this->bbfy && $hmx - $dim1 < $this->bbfx)
                {
                    $this->bboxx = $dim1;
                    $this->bboxy = $dim2;
                    $this->bboxz = $dim3;
                    $this->bbfx = $hmx - $dim1;
                    $this->bbfy = $dim2 - $hy;
                    $this->bbfz = abs($hz - $dim3);
                    $this->bboxi = $this->x;
                }
                else if ($dim2 - $hy == $this->bbfy && $hmx - $dim1 == $this->bbfx && abs($hz - $dim3) < $this->bbfz)
                {
                    $this->bboxx = $dim1;
                    $this->bboxy = $dim2;
                    $this->bboxz = $dim3;
                    $this->bbfx = $hmx - $dim1;
                    $this->bbfy = $dim2 - $hy;
                    $this->bbfz = abs($hz - $dim3);
                    $this->bboxi = $this->x;
                }
            }
        }
    }

    private function CheckFound()
    {
        $this->evened = false;

        if ($this->boxi != 0)
        {
            $this->cboxi = $this->boxi;
            $this->cboxx = $this->boxx;
            $this->cboxy = $this->boxy;
            $this->cboxz = $this->boxz;
        }
        else
        {
            if (($this->bboxi > 0) && ($this->layerinlayer != 0 || ($this->smallestZ->Pre == null && $this->smallestZ->Post == null)))
            {
                if ($this->layerinlayer == 0)
                {
                    $this->prelayer = $this->layerThickness;
                    $this->lilz = $this->smallestZ->CumZ;
                }

                $this->cboxi = $this->bboxi;
                $this->cboxx = $this->bboxx;
                $this->cboxy = $this->bboxy;
                $this->cboxz = $this->bboxz;
                $this->layerinlayer = $this->layerinlayer + $this->bboxy - $this->layerThickness;
                $this->layerThickness = $this->bboxy;
            }
            else
            {
                if ($this->smallestZ->Pre == null && $this->smallestZ->Post == null)
                {
                    $this->layerDone = true;
                }
                else
                {
                    $this->evened = true;

                    if ($this->smallestZ->Pre == null)
                    {
                        $this->trash = $this->smallestZ->Post;
                        $this->smallestZ->CumX = $this->smallestZ->Post->CumX;
                        $this->smallestZ->CumZ = $this->smallestZ->Post->CumZ;
                        $this->smallestZ->Post = $this->smallestZ->Post->Post;
                        if ($this->smallestZ->Post != null)
                        {
                            $this->smallestZ->Post->Pre = $this->smallestZ;
                        }
                    }
                    else if ($this->smallestZ->Post == null)
                    {
                        $this->smallestZ->Pre->Post = null;
                        $this->smallestZ->Pre->CumX = $this->smallestZ->CumX;
                    }
                    else
                    {
                        if ($this->smallestZ->Pre->CumZ == $this->smallestZ->Post->CumZ)
                        {
                            $this->smallestZ->Pre->Post = $this->smallestZ->Post->Post;

                            if ($this->smallestZ->Post->Post != null)
                            {
                                $this->smallestZ->Post->Post->Pre = $this->smallestZ->Pre;
                            }

                            $this->smallestZ->Pre->CumX = $this->smallestZ->Post->CumX;
                        }
                        else
                        {
                            $this->smallestZ->Pre->Post = $this->smallestZ->Post;
                            $this->smallestZ->Post->Pre = $this->smallestZ->Pre;

                            if ($this->smallestZ->Pre->CumZ < $this->smallestZ->Post->CumZ)
                            {
                                $this->smallestZ->Pre->CumX = $this->smallestZ->CumX;
                            }
                        }
                    }
                }
            }
        }
    }

    private function ExecuteIterations(Container $container)
    {
        $itelayer;
        $layersIndex;
        $bestVolume = 0.0;

        for ($containerOrientationVariant = 1; ($containerOrientationVariant <= 6) && !$this->quit; $containerOrientationVariant++)
        {
            switch ($containerOrientationVariant)
            {
                case 1:
                    $this->px = $container->Length; $this->py = $container->Height; $this->pz = $container->Width;
                    break;

                case 2:
                    $this->px = $container->Width; $this->py = $container->Height; $this->pz = $container->Length;
                    break;

                case 3:
                    $this->px = $container->Width; $this->py = $container->Length; $this->pz = $container->Height;
                    break;

                case 4:
                    $this->px = $container->Height; $this->py = $container->Length; $this->pz = $container->Width;
                    break;

                case 5:
                    $this->px = $container->Length; $this->py = $container->Width; $this->pz = $container->Height;
                    break;

                case 6:
                    $this->px = $container->Height; $this->py = $container->Width; $this->pz = $container->Length;
                    break;
            }

            $layerTmp = new Layer();
            $layerTmp->LayerEval = -1;
            $this->layers->append($layerTmp);
            $this->ListCanditLayers();
            $this->layers->uasort(function($a,$b){ return ($a->LayerEval == $b->LayerEval) ? 0 : (($a->LayerEval < $b->LayerEval) ? -1 : 1) });

            for ($layersIndex = 1; ($layersIndex <= $this->layerListLen) && !$this->quit; $layersIndex++)
            {
                $this->packedVolume = 0.0;
                $this->packedy = 0;
                $this->packing = true;
                $this->layerThickness = $this->layers[$layersIndex]->LayerDim;
                $itelayer = $layersIndex;
                $this->remainpy = $this->py;
                $this->remainpz = $this->pz;
                $this->packedItemCount = 0;

                for ($this->x = 1; $this->x <= $this->itemsToPackCount; $this->x++)
                {
                    $this->itemsToPack[$this->x]->IsPacked = false;
                }

                do
                {
                    $this->layerinlayer = 0;
                    $this->layerDone = false;

                    $this->PackLayer();

                    $this->packedy = $this->packedy + $this->layerThickness;
                    $this->remainpy = $this->py - $this->packedy;

                    if ($this->layerinlayer != 0 && !$this->quit)
                    {
                        $this->prepackedy = $this->packedy;
                        $this->preremainpy = $this->remainpy;
                        $this->remainpy = $this->layerThickness - $this->prelayer;
                        $this->packedy = $this->packedy - $this->layerThickness + $this->prelayer;
                        $this->remainpz = $this->lilz;
                        $this->layerThickness = $this->layerinlayer;
                        $this->layerDone = false;

                        $this->PackLayer();

                        $this->packedy = $this->prepackedy;
                        $this->remainpy = $this->preremainpy;
                        $this->remainpz = $this->pz;
                    }

                    FindLayer($this->remainpy);
                } while ($this->packing && !$this->quit);

                if (($this->packedVolume > $bestVolume) && !$this->quit)
                {
                    $bestVolume = $this->packedVolume;
                    bestVariant = $containerOrientationVariant;
                    bestIteration = $itelayer;
                }

                if ($this->hundredPercentPacked) break;
            }

            if ($this->hundredPercentPacked) break;

            if (($container->Length == $container->Height) && ($container->Height == $container->Width)) $containerOrientationVariant = 6;

            $this->layers = new ArrayObject();
        }
    }

    /// <summary>
    /// Finds the most proper $this->boxes by looking at all six possible orientations,
    /// empty space given, adjacent $this->boxes, and pallet limits.
    /// </summary>
    private function FindBox(float $hmx, float $hy, float $hmy, float $hz, float $hmz)
    {
        int $y;
        $this->bfx = 32767;
        $this->bfy = 32767;
        $this->bfz = 32767;
        $this->bbfx = 32767;
        $this->bbfy = 32767;
        $this->bbfz = 32767;
        $this->boxi = 0;
        $this->bboxi = 0;

        for (y = 1; y <= $this->itemsToPackCount; y = y + $this->itemsToPack[$y]->Quantity)
        {
            for (x = y; x < x + $this->itemsToPack[$y]->Quantity - 1; $this->x++)
            {
                if (!$this->itemsToPack[$this->x]->IsPacked) break;
            }

            if ($this->itemsToPack[$this->x]->IsPacked) continue;

            if (x > $this->itemsToPackCount) return;

            AnalyzeBox($hmx, $hy, $hmy, $hz, $hmz, $this->itemsToPack[$this->x]->Dim1, $this->itemsToPack[$this->x]->Dim2, $this->itemsToPack[$this->x]->Dim3);

            if (($this->itemsToPack[$this->x]->Dim1 == $this->itemsToPack[$this->x]->Dim3) && ($this->itemsToPack[$this->x]->Dim3 == $this->itemsToPack[$this->x]->Dim2)) continue;

            AnalyzeBox($hmx, $hy, $hmy, $hz, $hmz, $this->itemsToPack[$this->x]->Dim1, $this->itemsToPack[$this->x]->Dim3, $this->itemsToPack[$this->x]->Dim2);
            AnalyzeBox($hmx, $hy, $hmy, $hz, $hmz, $this->itemsToPack[$this->x]->Dim2, $this->itemsToPack[$this->x]->Dim1, $this->itemsToPack[$this->x]->Dim3);
            AnalyzeBox($hmx, $hy, $hmy, $hz, $hmz, $this->itemsToPack[$this->x]->Dim2, $this->itemsToPack[$this->x]->Dim3, $this->itemsToPack[$this->x]->Dim1);
            AnalyzeBox($hmx, $hy, $hmy, $hz, $hmz, $this->itemsToPack[$this->x]->Dim3, $this->itemsToPack[$this->x]->Dim1, $this->itemsToPack[$this->x]->Dim2);
            AnalyzeBox($hmx, $hy, $hmy, $hz, $hmz, $this->itemsToPack[$this->x]->Dim3, $this->itemsToPack[$this->x]->Dim2, $this->itemsToPack[$this->x]->Dim1);
        }
    }

    /// <summary>
    /// Finds the most proper layer height by looking at the unpacked $this->boxes and the remaining empty space available.
    /// </summary>
    private function FindLayer(float $thickness)
    {
        float $exdim = 0;
        float $dimdif;
        float $dimen2 = 0;
        float $dimen3 = 0;
        int $y;
        int $z;
        float $layereval;
        float $eval;
        $this->layerThickness = 0;
        eval = 1000000;

        for (x = 1; x <= $this->itemsToPackCount; $this->x++)
        {
            if ($this->itemsToPack[$this->x]->IsPacked) continue;

            for (y = 1; y <= 3; y++)
            {
                switch (y)
                {
                    case 1:
                        exdim = $this->itemsToPack[$this->x]->Dim1;
                        $dimen2 = $this->itemsToPack[$this->x]->Dim2;
                        $dimen3 = $this->itemsToPack[$this->x]->Dim3;
                        break;

                    case 2:
                        exdim = $this->itemsToPack[$this->x]->Dim2;
                        $dimen2 = $this->itemsToPack[$this->x]->Dim1;
                        $dimen3 = $this->itemsToPack[$this->x]->Dim3;
                        break;

                    case 3:
                        exdim = $this->itemsToPack[$this->x]->Dim3;
                        $dimen2 = $this->itemsToPack[$this->x]->Dim1;
                        $dimen3 = $this->itemsToPack[$this->x]->Dim2;
                        break;
                }

                layereval = 0;

                if ((exdim <= thickness) && ((($dimen2 <= $this->px) && ($dimen3 <= $this->pz)) || (($dimen3 <= $this->px) && ($dimen2 <= $this->pz))))
                {
                    for (z = 1; z <= $this->itemsToPackCount; z++)
                    {
                        if (!(x == z) && !($this->itemsToPack[z]->IsPacked))
                        {
                            $dimdif = abs(exdim - $this->itemsToPack[z]->Dim1);

                            if (abs(exdim - $this->itemsToPack[z]->Dim2) < $dimdif)
                            {
                                $dimdif = abs(exdim - $this->itemsToPack[z]->Dim2);
                            }

                            if (abs(exdim - $this->itemsToPack[z]->Dim3) < $dimdif)
                            {
                                $dimdif = abs(exdim - $this->itemsToPack[z]->Dim3);
                            }

                            layereval = layereval + $dimdif;
                        }
                    }

                    if (layereval < eval)
                    {
                        eval = layereval;
                        $this->layerThickness = exdim;
                    }
                }
            }
        }

        if ($this->layerThickness == 0 || $this->layerThickness > $this->remainpy) $this->packing = false;
    }

    /// <summary>
    /// Finds the first to be packed gap in the layer edge.
    /// </summary>
    private function FindSmallestZ()
    {
        ScrapPad scrapmemb = $this->scrapfirst;
        $this->smallestZ = scrapmemb;

        while (scrapmemb->Post != null)
        {
            if (scrapmemb->Post->CumZ < $this->smallestZ->CumZ)
            {
                $this->smallestZ = scrapmemb->Post;
            }

            scrapmemb = scrapmemb->Post;
        }
    }

    /// <summary>
    /// Initializes everything.
    /// </summary>
    private function Initialize(Container $container, ArrayObject $items)
    {
        $this->itemsToPack = new ArrayObject();
        $this->itemsPackedInOrder = new ArrayObject();
        $this->result = new ContainerPackingResult();

        // The original code uses 1-based indexing everywhere. This fake entry is added to the beginning
        // of the list to make that possible.
        $this->itemsToPack->append(new Item(0, 0, 0, 0, 0));

        $this->layers = new List<Layer>();
        $this->itemsToPackCount = 0;

        foreach (Item item in items)
        {
            for (int $i = 1; i <= item.Quantity; i++)
            {
                Item newItem = new Item(item.ID, item.Dim1, item.Dim2, item.Dim3, item.Quantity);
                $this->itemsToPack->append(newItem);
            }

            $this->itemsToPackCount += item.Quantity;
        }

        $this->itemsToPack->append(new Item(0, 0, 0, 0, 0));

        totalContainerVolume = $container->Length * $container->Height * $container->Width;
        $this->totalItemVolume = 0.0;

        for (x = 1; x <= $this->itemsToPackCount; $this->x++)
        {
            $this->totalItemVolume = $this->totalItemVolume + $this->itemsToPack[$this->x]->Volume;
        }

        $this->scrapfirst = new ScrapPad();

        $this->scrapfirst->Pre = null;
        $this->scrapfirst->Post = null;
        $this->packingBest = false;
        $this->hundredPercentPacked = false;
        $this->quit = false;
    }

    /// <summary>
    /// Lists all possible layer heights by giving a weight value to each of them.
    /// </summary>
    private function ListCanditLayers()
    {
        bool $same;
        float $exdim = 0;
        float $dimdif;
        float $dimen2 = 0;
        float $dimen3 = 0;
        int $y;
        int $z;
        int $k;
        float $layereval;

        $this->layerListLen = 0;

        for (x = 1; x <= $this->itemsToPackCount; $this->x++)
        {
            for (y = 1; y <= 3; y++)
            {
                switch (y)
                {
                    case 1:
                        exdim = $this->itemsToPack[$this->x]->Dim1;
                        $dimen2 = $this->itemsToPack[$this->x]->Dim2;
                        $dimen3 = $this->itemsToPack[$this->x]->Dim3;
                        break;

                    case 2:
                        exdim = $this->itemsToPack[$this->x]->Dim2;
                        $dimen2 = $this->itemsToPack[$this->x]->Dim1;
                        $dimen3 = $this->itemsToPack[$this->x]->Dim3;
                        break;

                    case 3:
                        exdim = $this->itemsToPack[$this->x]->Dim3;
                        $dimen2 = $this->itemsToPack[$this->x]->Dim1;
                        $dimen3 = $this->itemsToPack[$this->x]->Dim2;
                        break;
                }

                if ((exdim > $this->py) || ((($dimen2 > $this->px) || ($dimen3 > $this->pz)) && (($dimen3 > $this->px) || ($dimen2 > $this->pz)))) continue;

                same = false;

                for (k = 1; k <= $this->layerListLen; k++)
                {
                    if (exdim == $this->layers[k]->LayerDim)
                    {
                        same = true;
                        continue;
                    }
                }

                if (same) continue;

                layereval = 0;

                for (z = 1; z <= $this->itemsToPackCount; z++)
                {
                    if (!(x == z))
                    {
                        $dimdif = abs(exdim - $this->itemsToPack[z]->Dim1);

                        if (abs(exdim - $this->itemsToPack[z]->Dim2) < $dimdif)
                        {
                            $dimdif = abs(exdim - $this->itemsToPack[z]->Dim2);
                        }
                        if (abs(exdim - $this->itemsToPack[z]->Dim3) < $dimdif)
                        {
                            $dimdif = abs(exdim - $this->itemsToPack[z]->Dim3);
                        }
                        layereval = layereval + $dimdif;
                    }
                }

                $this->layerListLen++;

                $this->layers->append(new Layer());
                $this->layers[$this->layerListLen]->LayerEval = layereval;
                $this->layers[$this->layerListLen]->LayerDim = exdim;
            }
        }
    }

    /// <summary>
    /// Transforms the found coordinate system to the one entered by the user and writes them
    /// to the report file.
    /// </summary>
    private function OutputBoxList()
    {
        float $packCoordX = 0;
        float $packCoordY = 0;
        float $packCoordZ = 0;
        dynamic packDimX = 0;
        dynamic packDimY = 0;
        dynamic packDimZ = 0;

        switch (bestVariant)
        {
            case 1:
                packCoordX = $this->itemsToPack[$this->cboxi]->CoordX;
                packCoordY = $this->itemsToPack[$this->cboxi]->CoordY;
                packCoordZ = $this->itemsToPack[$this->cboxi]->CoordZ;
                packDimX = $this->itemsToPack[$this->cboxi]->PackDimX;
                packDimY = $this->itemsToPack[$this->cboxi]->PackDimY;
                packDimZ = $this->itemsToPack[$this->cboxi]->PackDimZ;
                break;

            case 2:
                packCoordX = $this->itemsToPack[$this->cboxi]->CoordZ;
                packCoordY = $this->itemsToPack[$this->cboxi]->CoordY;
                packCoordZ = $this->itemsToPack[$this->cboxi]->CoordX;
                packDimX = $this->itemsToPack[$this->cboxi]->PackDimZ;
                packDimY = $this->itemsToPack[$this->cboxi]->PackDimY;
                packDimZ = $this->itemsToPack[$this->cboxi]->PackDimX;
                break;

            case 3:
                packCoordX = $this->itemsToPack[$this->cboxi]->CoordY;
                packCoordY = $this->itemsToPack[$this->cboxi]->CoordZ;
                packCoordZ = $this->itemsToPack[$this->cboxi]->CoordX;
                packDimX = $this->itemsToPack[$this->cboxi]->PackDimY;
                packDimY = $this->itemsToPack[$this->cboxi]->PackDimZ;
                packDimZ = $this->itemsToPack[$this->cboxi]->PackDimX;
                break;

            case 4:
                packCoordX = $this->itemsToPack[$this->cboxi]->CoordY;
                packCoordY = $this->itemsToPack[$this->cboxi]->CoordX;
                packCoordZ = $this->itemsToPack[$this->cboxi]->CoordZ;
                packDimX = $this->itemsToPack[$this->cboxi]->PackDimY;
                packDimY = $this->itemsToPack[$this->cboxi]->PackDimX;
                packDimZ = $this->itemsToPack[$this->cboxi]->PackDimZ;
                break;

            case 5:
                packCoordX = $this->itemsToPack[$this->cboxi]->CoordX;
                packCoordY = $this->itemsToPack[$this->cboxi]->CoordZ;
                packCoordZ = $this->itemsToPack[$this->cboxi]->CoordY;
                packDimX = $this->itemsToPack[$this->cboxi]->PackDimX;
                packDimY = $this->itemsToPack[$this->cboxi]->PackDimZ;
                packDimZ = $this->itemsToPack[$this->cboxi]->PackDimY;
                break;

            case 6:
                packCoordX = $this->itemsToPack[$this->cboxi]->CoordZ;
                packCoordY = $this->itemsToPack[$this->cboxi]->CoordX;
                packCoordZ = $this->itemsToPack[$this->cboxi]->CoordY;
                packDimX = $this->itemsToPack[$this->cboxi]->PackDimZ;
                packDimY = $this->itemsToPack[$this->cboxi]->PackDimX;
                packDimZ = $this->itemsToPack[$this->cboxi]->PackDimY;
                break;
        }

        $this->itemsToPack[$this->cboxi]->CoordX = packCoordX;
        $this->itemsToPack[$this->cboxi]->CoordY = packCoordY;
        $this->itemsToPack[$this->cboxi]->CoordZ = packCoordZ;
        $this->itemsToPack[$this->cboxi]->PackDimX = packDimX;
        $this->itemsToPack[$this->cboxi]->PackDimY = packDimY;
        $this->itemsToPack[$this->cboxi]->PackDimZ = packDimZ;

        $this->itemsPackedInOrder->append($this->itemsToPack[$this->cboxi]);
    }

    /// <summary>
    /// Packs the $this->boxes found and arranges all variables and records properly.
    /// </summary>
    private function PackLayer()
    {
        float $lenx;
        float $lenz;
        float $lpz;

        if ($this->layerThickness == 0)
        {
            $this->packing = false;
            return;
        }

        $this->scrapfirst->CumX = $this->px;
        $this->scrapfirst->CumZ = 0;

        for (; !$this->quit;)
        {
            FindSmallestZ();

            if (($this->smallestZ->Pre == null) && ($this->smallestZ->Post == null))
            {
                //*** SITUATION-1: NO $this->boxES ON THE RIGHT AND LEFT SIDES ***

                lenx = $this->smallestZ->CumX;
                lpz = $this->remainpz - $this->smallestZ->CumZ;
                FindBox(lenx, $this->layerThickness, $this->remainpy, lpz, lpz);
                CheckFound();

                if ($this->layerDone) break;
                if ($this->evened) continue;

                $this->itemsToPack[$this->cboxi]->CoordX = 0;
                $this->itemsToPack[$this->cboxi]->CoordY = $this->packedy;
                $this->itemsToPack[$this->cboxi]->CoordZ = $this->smallestZ->CumZ;
                if ($this->cboxx == $this->smallestZ->CumX)
                {
                    $this->smallestZ->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                }
                else
                {
                    $this->smallestZ->Post = new ScrapPad();

                    $this->smallestZ->Post->Post = null;
                    $this->smallestZ->Post->Pre = $this->smallestZ;
                    $this->smallestZ->Post->CumX = $this->smallestZ->CumX;
                    $this->smallestZ->Post->CumZ = $this->smallestZ->CumZ;
                    $this->smallestZ->CumX = $this->cboxx;
                    $this->smallestZ->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                }
            }
            else if ($this->smallestZ->Pre == null)
            {
                //*** SITUATION-2: NO $this->boxES ON THE LEFT SIDE ***

                lenx = $this->smallestZ->CumX;
                lenz = $this->smallestZ->Post->CumZ - $this->smallestZ->CumZ;
                lpz = $this->remainpz - $this->smallestZ->CumZ;
                FindBox(lenx, $this->layerThickness, $this->remainpy, lenz, lpz);
                CheckFound();

                if ($this->layerDone) break;
                if ($this->evened) continue;

                $this->itemsToPack[$this->cboxi]->CoordY = $this->packedy;
                $this->itemsToPack[$this->cboxi]->CoordZ = $this->smallestZ->CumZ;
                if ($this->cboxx == $this->smallestZ->CumX)
                {
                    $this->itemsToPack[$this->cboxi]->CoordX = 0;

                    if ($this->smallestZ->CumZ + $this->cboxz == $this->smallestZ->Post->CumZ)
                    {
                        $this->smallestZ->CumZ = $this->smallestZ->Post->CumZ;
                        $this->smallestZ->CumX = $this->smallestZ->Post->CumX;
                        $this->trash = $this->smallestZ->Post;
                        $this->smallestZ->Post = $this->smallestZ->Post->Post;

                        if ($this->smallestZ->Post != null)
                        {
                            $this->smallestZ->Post->Pre = $this->smallestZ;
                        }
                    }
                    else
                    {
                        $this->smallestZ->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                    }
                }
                else
                {
                    $this->itemsToPack[$this->cboxi]->CoordX = $this->smallestZ->CumX - $this->cboxx;

                    if ($this->smallestZ->CumZ + $this->cboxz == $this->smallestZ->Post->CumZ)
                    {
                        $this->smallestZ->CumX = $this->smallestZ->CumX - $this->cboxx;
                    }
                    else
                    {
                        $this->smallestZ->Post->Pre = new ScrapPad();

                        $this->smallestZ->Post->Pre->Post = $this->smallestZ->Post;
                        $this->smallestZ->Post->Pre->Pre = $this->smallestZ;
                        $this->smallestZ->Post = $this->smallestZ->Post->Pre;
                        $this->smallestZ->Post->CumX = $this->smallestZ->CumX;
                        $this->smallestZ->CumX = $this->smallestZ->CumX - $this->cboxx;
                        $this->smallestZ->Post->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                    }
                }
            }
            else if ($this->smallestZ->Post == null)
            {
                //*** SITUATION-3: NO $this->boxES ON THE RIGHT SIDE ***

                lenx = $this->smallestZ->CumX - $this->smallestZ->Pre->CumX;
                lenz = $this->smallestZ->Pre->CumZ - $this->smallestZ->CumZ;
                lpz = $this->remainpz - $this->smallestZ->CumZ;
                FindBox(lenx, $this->layerThickness, $this->remainpy, lenz, lpz);
                CheckFound();

                if ($this->layerDone) break;
                if ($this->evened) continue;

                $this->itemsToPack[$this->cboxi]->CoordY = $this->packedy;
                $this->itemsToPack[$this->cboxi]->CoordZ = $this->smallestZ->CumZ;
                $this->itemsToPack[$this->cboxi]->CoordX = $this->smallestZ->Pre->CumX;

                if ($this->cboxx == $this->smallestZ->CumX - $this->smallestZ->Pre->CumX)
                {
                    if ($this->smallestZ->CumZ + $this->cboxz == $this->smallestZ->Pre->CumZ)
                    {
                        $this->smallestZ->Pre->CumX = $this->smallestZ->CumX;
                        $this->smallestZ->Pre->Post = null;
                    }
                    else
                    {
                        $this->smallestZ->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                    }
                }
                else
                {
                    if ($this->smallestZ->CumZ + $this->cboxz == $this->smallestZ->Pre->CumZ)
                    {
                        $this->smallestZ->Pre->CumX = $this->smallestZ->Pre->CumX + $this->cboxx;
                    }
                    else
                    {
                        $this->smallestZ->Pre->Post = new ScrapPad();

                        $this->smallestZ->Pre->Post->Pre = $this->smallestZ->Pre;
                        $this->smallestZ->Pre->Post->Post = $this->smallestZ;
                        $this->smallestZ->Pre = $this->smallestZ->Pre->Post;
                        $this->smallestZ->Pre->CumX = $this->smallestZ->Pre->Pre->CumX + $this->cboxx;
                        $this->smallestZ->Pre->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                    }
                }
            }
            else if ($this->smallestZ->Pre->CumZ == $this->smallestZ->Post->CumZ)
            {
                //*** SITUATION-4: THERE ARE $this->boxES ON BOTH OF THE SIDES ***

                //*** SUBSITUATION-4A: SIDES ARE EQUAL TO EACH OTHER ***

                lenx = $this->smallestZ->CumX - $this->smallestZ->Pre->CumX;
                lenz = $this->smallestZ->Pre->CumZ - $this->smallestZ->CumZ;
                lpz = $this->remainpz - $this->smallestZ->CumZ;

                FindBox(lenx, $this->layerThickness, $this->remainpy, lenz, lpz);
                CheckFound();

                if ($this->layerDone) break;
                if ($this->evened) continue;

                $this->itemsToPack[$this->cboxi]->CoordY = $this->packedy;
                $this->itemsToPack[$this->cboxi]->CoordZ = $this->smallestZ->CumZ;

                if ($this->cboxx == $this->smallestZ->CumX - $this->smallestZ->Pre->CumX)
                {
                    $this->itemsToPack[$this->cboxi]->CoordX = $this->smallestZ->Pre->CumX;

                    if ($this->smallestZ->CumZ + $this->cboxz == $this->smallestZ->Post->CumZ)
                    {
                        $this->smallestZ->Pre->CumX = $this->smallestZ->Post->CumX;

                        if ($this->smallestZ->Post->Post != null)
                        {
                            $this->smallestZ->Pre->Post = $this->smallestZ->Post->Post;
                            $this->smallestZ->Post->Post->Pre = $this->smallestZ->Pre;
                        }
                        else
                        {
                            $this->smallestZ->Pre->Post = null;
                        }
                    }
                    else
                    {
                        $this->smallestZ->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                    }
                }
                else if ($this->smallestZ->Pre->CumX < $this->px - $this->smallestZ->CumX)
                {
                    if ($this->smallestZ->CumZ + $this->cboxz == $this->smallestZ->Pre->CumZ)
                    {
                        $this->smallestZ->CumX = $this->smallestZ->CumX - $this->cboxx;
                        $this->itemsToPack[$this->cboxi]->CoordX = $this->smallestZ->CumX - $this->cboxx;
                    }
                    else
                    {
                        $this->itemsToPack[$this->cboxi]->CoordX = $this->smallestZ->Pre->CumX;
                        $this->smallestZ->Pre->Post = new ScrapPad();

                        $this->smallestZ->Pre->Post->Pre = $this->smallestZ->Pre;
                        $this->smallestZ->Pre->Post->Post = $this->smallestZ;
                        $this->smallestZ->Pre = $this->smallestZ->Pre->Post;
                        $this->smallestZ->Pre->CumX = $this->smallestZ->Pre->Pre->CumX + $this->cboxx;
                        $this->smallestZ->Pre->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                    }
                }
                else
                {
                    if ($this->smallestZ->CumZ + $this->cboxz == $this->smallestZ->Pre->CumZ)
                    {
                        $this->smallestZ->Pre->CumX = $this->smallestZ->Pre->CumX + $this->cboxx;
                        $this->itemsToPack[$this->cboxi]->CoordX = $this->smallestZ->Pre->CumX;
                    }
                    else
                    {
                        $this->itemsToPack[$this->cboxi]->CoordX = $this->smallestZ->CumX - $this->cboxx;
                        $this->smallestZ->Post->Pre = new ScrapPad();

                        $this->smallestZ->Post->Pre->Post = $this->smallestZ->Post;
                        $this->smallestZ->Post->Pre->Pre = $this->smallestZ;
                        $this->smallestZ->Post = $this->smallestZ->Post->Pre;
                        $this->smallestZ->Post->CumX = $this->smallestZ->CumX;
                        $this->smallestZ->Post->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                        $this->smallestZ->CumX = $this->smallestZ->CumX - $this->cboxx;
                    }
                }
            }
            else
            {
                //*** SUBSITUATION-4B: SIDES ARE NOT EQUAL TO EACH OTHER ***

                lenx = $this->smallestZ->CumX - $this->smallestZ->Pre->CumX;
                lenz = $this->smallestZ->Pre->CumZ - $this->smallestZ->CumZ;
                lpz = $this->remainpz - $this->smallestZ->CumZ;
                FindBox(lenx, $this->layerThickness, $this->remainpy, lenz, lpz);
                CheckFound();

                if ($this->layerDone) break;
                if ($this->evened) continue;

                $this->itemsToPack[$this->cboxi]->CoordY = $this->packedy;
                $this->itemsToPack[$this->cboxi]->CoordZ = $this->smallestZ->CumZ;
                $this->itemsToPack[$this->cboxi]->CoordX = $this->smallestZ->Pre->CumX;

                if ($this->cboxx == ($this->smallestZ->CumX - $this->smallestZ->Pre->CumX))
                {
                    if (($this->smallestZ->CumZ + $this->cboxz) == $this->smallestZ->Pre->CumZ)
                    {
                        $this->smallestZ->Pre->CumX = $this->smallestZ->CumX;
                        $this->smallestZ->Pre->Post = $this->smallestZ->Post;
                        $this->smallestZ->Post->Pre = $this->smallestZ->Pre;
                    }
                    else
                    {
                        $this->smallestZ->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                    }
                }
                else
                {
                    if (($this->smallestZ->CumZ + $this->cboxz) == $this->smallestZ->Pre->CumZ)
                    {
                        $this->smallestZ->Pre->CumX = $this->smallestZ->Pre->CumX + $this->cboxx;
                    }
                    else if ($this->smallestZ->CumZ + $this->cboxz == $this->smallestZ->Post->CumZ)
                    {
                        $this->itemsToPack[$this->cboxi]->CoordX = $this->smallestZ->CumX - $this->cboxx;
                        $this->smallestZ->CumX = $this->smallestZ->CumX - $this->cboxx;
                    }
                    else
                    {
                        $this->smallestZ->Pre->Post = new ScrapPad();

                        $this->smallestZ->Pre->Post->Pre = $this->smallestZ->Pre;
                        $this->smallestZ->Pre->Post->Post = $this->smallestZ;
                        $this->smallestZ->Pre = $this->smallestZ->Pre->Post;
                        $this->smallestZ->Pre->CumX = $this->smallestZ->Pre->Pre->CumX + $this->cboxx;
                        $this->smallestZ->Pre->CumZ = $this->smallestZ->CumZ + $this->cboxz;
                    }
                }
            }

            VolumeCheck();
        }
    }

    /// <summary>
    /// Using the parameters found, packs the best solution found and
    /// reports to the console.
    /// </summary>
    private function Report(Container $container)
    {
        $this->quit = false;

        switch (bestVariant)
        {
            case 1:
                $this->px = $container->Length; $this->py = $container->Height; $this->pz = $container->Width;
                break;

            case 2:
                $this->px = $container->Width; $this->py = $container->Height; $this->pz = $container->Length;
                break;

            case 3:
                $this->px = $container->Width; $this->py = $container->Length; $this->pz = $container->Height;
                break;

            case 4:
                $this->px = $container->Height; $this->py = $container->Length; $this->pz = $container->Width;
                break;

            case 5:
                $this->px = $container->Length; $this->py = $container->Width; $this->pz = $container->Height;
                break;

            case 6:
                $this->px = $container->Height; $this->py = $container->Width; $this->pz = $container->Length;
                break;
        }

        $this->packingBest = true;

        //Print("BEST SOLUTION FOUND AT ITERATION                      :", bestIteration, "OF VARIANT", bestVariant);
        //Print("TOTAL ITEMS TO PACK                                   :", $this->itemsToPackCount);
        //Print("TOTAL VOLUME OF ALL ITEMS                             :", $this->totalItemVolume);
        //Print("WHILE CONTAINER ORIENTATION X - Y - Z                 :", $this->px, $this->py, $this->pz);

        $this->layers->Clear();
        $this->layers->append(new Layer { LayerEval = -1 });
        $this->ListCanditLayers();
        $this->layers = $this->layers->OrderBy(l => l.LayerEval).ToList();
        $this->packedVolume = 0;
        $this->packedy = 0;
        $this->packing = true;
        $this->layerThickness = $this->layers[bestIteration]->LayerDim;
        $this->remainpy = $this->py;
        $this->remainpz = $this->pz;

        for (x = 1; x <= $this->itemsToPackCount; $this->x++)
        {
            $this->itemsToPack[$this->x]->IsPacked = false;
        }

        do
        {
            $this->layerinlayer = 0;
            $this->layerDone = false;
            PackLayer();
            $this->packedy = $this->packedy + $this->layerThickness;
            $this->remainpy = $this->py - $this->packedy;

            if ($this->layerinlayer > 0.0001)
            {
                $this->prepackedy = $this->packedy;
                $this->preremainpy = $this->remainpy;
                $this->remainpy = $this->layerThickness - $this->prelayer;
                $this->packedy = $this->packedy - $this->layerThickness + $this->prelayer;
                $this->remainpz = $this->lilz;
                $this->layerThickness = $this->layerinlayer;
                $this->layerDone = false;
                PackLayer();
                $this->packedy = $this->prepackedy;
                $this->remainpy = $this->preremainpy;
                $this->remainpz = $this->pz;
            }

            if (!$this->quit)
            {
                FindLayer($this->remainpy);
            }
        } while ($this->packing && !$this->quit);
    }

    /// <summary>
    /// After $this->packing of each item, the 100% $this->packing condition is checked.
    /// </summary>
    private function VolumeCheck()
    {
        $this->itemsToPack[$this->cboxi]->IsPacked = true;
        $this->itemsToPack[$this->cboxi]->PackDimX = $this->cboxx;
        $this->itemsToPack[$this->cboxi]->PackDimY = $this->cboxy;
        $this->itemsToPack[$this->cboxi]->PackDimZ = $this->cboxz;
        $this->packedVolume = $this->packedVolume + $this->itemsToPack[$this->cboxi]->Volume;
        $this->packedItemCount++;

        if ($this->packingBest)
        {
            OutputBoxList();
        }
        else if ($this->packedVolume == totalContainerVolume || $this->packedVolume == $this->totalItemVolume)
        {
            $this->packing = false;
            $this->hundredPercentPacked = true;
        }
    }
}