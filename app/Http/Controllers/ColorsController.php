<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Validator;
use App\Attribute;
use App\Color;
use App\AttributeColor;
use App\AttributeDimension;
use App\Dimension;

class ColorsController extends Controller
{
  // am facut asta la cererea lor de a importa dintr-un fisier culorile, pentru a nu fi introduse manual de catre ei. 
  // Ignor asta pentru ca trebuie, la cererea lor, sa schimb toate json-urile in tabele separate, cu legaturi intre ele
//   public function generatePDF(Request $request){
  public function uploadColors(){
    
    $attrs = '[
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "3005"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "3005 MAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "3009"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "3009 MAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "3011"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "3011 MAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "5005"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "6005"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "6005 MAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "6020 MAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "7016 TOPMAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "7024"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "7024 MAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "8004"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "8004 MAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "8015 TOPMAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "8017"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "8017 ICEMAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "8017 MAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "8019 MAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "8019 TOPMAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "9002"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "9005 ICEMAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "9005 MAT"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "BRONZ"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "CAMUFLAJ"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "NUC"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "NUC-D"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "PIATRA"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "PIN-NOBIL"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "STEJAR"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "STEJAR ALB"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "STEJAR-D"
 },
 {
   "name": "Culoare produs",
   "id": 13,
   "value RAL": "ZINCAT"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "3005"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "3009"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "3011"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "5005"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "6005"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "6020"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "7024"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "8004"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "8017"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "8019"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "9005"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "9010"
 },
 {
   "name": "Culoare sistem scurgere",
   "id": 15,
   "value RAL": "ZINCAT"
 },
 {
   "name": "Culoare surub sipca",
   "id": 17,
   "value RAL": "3005"
 },
 {
   "name": "Culoare surub sipca",
   "id": 17,
   "value RAL": "3009"
 },
 {
   "name": "Culoare surub sipca",
   "id": 17,
   "value RAL": "6005"
 },
 {
   "name": "Culoare surub sipca",
   "id": 17,
   "value RAL": "8004"
 },
 {
   "name": "Culoare surub sipca",
   "id": 17,
   "value RAL": "8017"
 },
 {
   "name": "Culoare surub sipca",
   "id": 17,
   "value RAL": "8019"
 },
 {
   "name": "Culoare surub sipca",
   "id": 17,
   "value RAL": "9006"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "3005"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "3009"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "3011"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "5005"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "6005"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "6020"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "7024"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "8004"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "8017"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "8019"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "9002"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "9005"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "9010"
 },
 {
   "name": "Culoare surub tabla",
   "id": 14,
   "value RAL": "ZINCAT"
 },
 {
   "name": "Dimensiune sistem scurgere",
   "id": 16,
   "value RAL": "125/087"
 },
 {
   "name": "Dimensiune sistem scurgere",
   "id": 16,
   "value RAL": "125/087 eco"
 },
 {
   "name": "Dimensiune sistem scurgere",
   "id": 16,
   "value RAL": "150/100"
 },
 {
   "name": "Grosime produs",
   "id": 12,
   "value RAL": "0.35"
 },
 {
   "name": "Grosime produs",
   "id": 12,
   "value RAL": "0.4"
 },
 {
   "name": "Grosime produs",
   "id": 12,
   "value RAL": "0.45"
 },
 {
   "name": "Grosime produs",
   "id": 12,
   "value RAL": "0.5"
 }
]';
    $colors = '
    [
 {
   "RAL": 1000,
   "RGB": "190-189-127",
   "HEX": "#BEBD7F"
 },
 {
   "RAL": 1001,
   "RGB": "194-176-120",
   "HEX": "#C2B078"
 },
 {
   "RAL": 1002,
   "RGB": "198-166-100",
   "HEX": "#C6A664"
 },
 {
   "RAL": 1003,
   "RGB": "229-190-001",
   "HEX": "#E5BE01"
 },
 {
   "RAL": 1004,
   "RGB": "205-164-052",
   "HEX": "#CDA434"
 },
 {
   "RAL": 1005,
   "RGB": "169-131-007",
   "HEX": "#A98307"
 },
 {
   "RAL": 1006,
   "RGB": "228-160-016",
   "HEX": "#E4A010"
 },
 {
   "RAL": 1007,
   "RGB": "220-156-000",
   "HEX": "#DC9D00"
 },
 {
   "RAL": 1011,
   "RGB": "138-102-066",
   "HEX": "#8A6642"
 },
 {
   "RAL": 1012,
   "RGB": "199-180-070",
   "HEX": "#C7B446"
 },
 {
   "RAL": 1013,
   "RGB": "234-230-202",
   "HEX": "#EAE6CA"
 },
 {
   "RAL": 1014,
   "RGB": "225-204-079",
   "HEX": "#E1CC4F"
 },
 {
   "RAL": 1015,
   "RGB": "230-214-144",
   "HEX": "#E6D690"
 },
 {
   "RAL": 1016,
   "RGB": "237-255-033",
   "HEX": "#EDFF21"
 },
 {
   "RAL": 1017,
   "RGB": "245-208-051",
   "HEX": "#F5D033"
 },
 {
   "RAL": 1018,
   "RGB": "248-243-053",
   "HEX": "#F8F32B"
 },
 {
   "RAL": 1019,
   "RGB": "158-151-100",
   "HEX": "#9E9764"
 },
 {
   "RAL": 1020,
   "RGB": "153-153-080",
   "HEX": "#999950"
 },
 {
   "RAL": 1021,
   "RGB": "243-218-011",
   "HEX": "#F3DA0B"
 },
 {
   "RAL": 1023,
   "RGB": "250-210-001",
   "HEX": "#FAD201"
 },
 {
   "RAL": 1024,
   "RGB": "174-160-075",
   "HEX": "#AEA04B"
 },
 {
   "RAL": 1026,
   "RGB": "255-255-000",
   "HEX": "#FFFF00"
 },
 {
   "RAL": 1027,
   "RGB": "157-145-001",
   "HEX": "#9D9101"
 },
 {
   "RAL": 1028,
   "RGB": "244-169-000",
   "HEX": "#F4A900"
 },
 {
   "RAL": 1032,
   "RGB": "214-174-001",
   "HEX": "#D6AE01"
 },
 {
   "RAL": 1033,
   "RGB": "243-165-005",
   "HEX": "#F3A505"
 },
 {
   "RAL": 1034,
   "RGB": "239-169-074",
   "HEX": "#EFA94A"
 },
 {
   "RAL": 1035,
   "RGB": "106-093-077",
   "HEX": "#6A5D4D"
 },
 {
   "RAL": 1036,
   "RGB": "112-083-053",
   "HEX": "#705335"
 },
 {
   "RAL": 1037,
   "RGB": "243-159-024",
   "HEX": "#F39F18"
 },
 {
   "RAL": 2000,
   "RGB": "237-118-014",
   "HEX": "#ED760E"
 },
 {
   "RAL": 2001,
   "RGB": "201-060-032",
   "HEX": "#C93C20"
 },
 {
   "RAL": 2002,
   "RGB": "203-040-033",
   "HEX": "#CB2821"
 },
 {
   "RAL": 2003,
   "RGB": "255-117-020",
   "HEX": "#FF7514"
 },
 {
   "RAL": 2004,
   "RGB": "244-070-017",
   "HEX": "#F44611"
 },
 {
   "RAL": 2005,
   "RGB": "255-035-001",
   "HEX": "#FF2301"
 },
 {
   "RAL": 2007,
   "RGB": "255-164-032",
   "HEX": "#FFA420"
 },
 {
   "RAL": 2008,
   "RGB": "247-094-037",
   "HEX": "#F75E25"
 },
 {
   "RAL": 2009,
   "RGB": "245-064-033",
   "HEX": "#F54021"
 },
 {
   "RAL": 2010,
   "RGB": "216-075-032",
   "HEX": "#D84B20"
 },
 {
   "RAL": 2011,
   "RGB": "236-124-038",
   "HEX": "#EC7C26"
 },
 {
   "RAL": 2012,
   "RGB": "235-106-014",
   "HEX": "#E55137"
 },
 {
   "RAL": 2013,
   "RGB": "195-088-049",
   "HEX": "#C35831"
 },
 {
   "RAL": 3000,
   "RGB": "175-043-030",
   "HEX": "#AF2B1E"
 },
 {
   "RAL": 3001,
   "RGB": "165-032-025",
   "HEX": "#A52019"
 },
 {
   "RAL": 3002,
   "RGB": "162-035-029",
   "HEX": "#A2231D"
 },
 {
   "RAL": 3003,
   "RGB": "155-017-030",
   "HEX": "#9B111E"
 },
 {
   "RAL": 3004,
   "RGB": "117-021-030",
   "HEX": "#75151E"
 },
 {
   "RAL": 3005,
   "RGB": "094-033-041",
   "HEX": "#5E2129"
 },
 {
   "RAL": 3007,
   "RGB": "065-034-039",
   "HEX": "#412227"
 },
 {
   "RAL": 3009,
   "RGB": "100-036-036",
   "HEX": "#642424"
 },
 {
   "RAL": 3011,
   "RGB": "120-031-025",
   "HEX": "#781F19"
 },
 {
   "RAL": 3012,
   "RGB": "193-135-107",
   "HEX": "#C1876B"
 },
 {
   "RAL": 3013,
   "RGB": "161-035-018",
   "HEX": "#A12312"
 },
 {
   "RAL": 3014,
   "RGB": "211-110-112",
   "HEX": "#D36E70"
 },
 {
   "RAL": 3015,
   "RGB": "234-137-154",
   "HEX": "#EA899A"
 },
 {
   "RAL": 3016,
   "RGB": "179-040-033",
   "HEX": "#B32821"
 },
 {
   "RAL": 3017,
   "RGB": "230-050-068",
   "HEX": "#E63244"
 },
 {
   "RAL": 3018,
   "RGB": "213-048-050",
   "HEX": "#D53032"
 },
 {
   "RAL": 3020,
   "RGB": "204-006-005",
   "HEX": "#CC0605"
 },
 {
   "RAL": 3022,
   "RGB": "217-080-048",
   "HEX": "#D95030"
 },
 {
   "RAL": 3024,
   "RGB": "248-000-000",
   "HEX": "#F80000"
 },
 {
   "RAL": 3026,
   "RGB": "254-000-000",
   "HEX": "#FE0000"
 },
 {
   "RAL": 3027,
   "RGB": "197-029-052",
   "HEX": "#C51D34"
 },
 {
   "RAL": 3028,
   "RGB": "203-050-052",
   "HEX": "#CB3234"
 },
 {
   "RAL": 3031,
   "RGB": "179-036-040",
   "HEX": "#B32428"
 },
 {
   "RAL": 3032,
   "RGB": "114-020-034",
   "HEX": "#721422"
 },
 {
   "RAL": 3033,
   "RGB": "180-076-067",
   "HEX": "#B44C43"
 },
 {
   "RAL": 4001,
   "RGB": "109-063-091",
   "HEX": "#6D3F5B"
 },
 {
   "RAL": 4002,
   "RGB": "146-043-062",
   "HEX": "#922B3E"
 },
 {
   "RAL": 4003,
   "RGB": "222-076-138",
   "HEX": "#DE4C8A"
 },
 {
   "RAL": 4004,
   "RGB": "110-028-052",
   "HEX": "#641C34"
 },
 {
   "RAL": 4005,
   "RGB": "108-070-117",
   "HEX": "#6C4675"
 },
 {
   "RAL": 4006,
   "RGB": "160-052-114",
   "HEX": "#A03472"
 },
 {
   "RAL": 4007,
   "RGB": "074-025-044",
   "HEX": "#4A192C"
 },
 {
   "RAL": 4008,
   "RGB": "144-070-132",
   "HEX": "#924E7D"
 },
 {
   "RAL": 4009,
   "RGB": "164-125-144",
   "HEX": "#A18594"
 },
 {
   "RAL": 4010,
   "RGB": "215-045-109",
   "HEX": "#CF3476"
 },
 {
   "RAL": 4011,
   "RGB": "134-115-161",
   "HEX": "#8673A1"
 },
 {
   "RAL": 4012,
   "RGB": "108-104-129",
   "HEX": "#6C6874"
 },
 {
   "RAL": 5000,
   "RGB": "042-046-075",
   "HEX": "#354D73"
 },
 {
   "RAL": 5001,
   "RGB": "031-052-056",
   "HEX": "#1F3438"
 },
 {
   "RAL": 5002,
   "RGB": "032-033-079",
   "HEX": "#20214F"
 },
 {
   "RAL": 5003,
   "RGB": "029-030-051",
   "HEX": "#1D1E33"
 },
 {
   "RAL": 5004,
   "RGB": "024-023-028",
   "HEX": "#18171C"
 },
 {
   "RAL": 5005,
   "RGB": "030-045-110",
   "HEX": "#1E2460"
 },
 {
   "RAL": 5007,
   "RGB": "062-095-138",
   "HEX": "#3E5F8A"
 },
 {
   "RAL": 5008,
   "RGB": "038-037-045",
   "HEX": "#26252D"
 },
 {
   "RAL": 5009,
   "RGB": "002-086-105",
   "HEX": "#025669"
 },
 {
   "RAL": 5010,
   "RGB": "014-041-075",
   "HEX": "#0E294B"
 },
 {
   "RAL": 5011,
   "RGB": "035-026-036",
   "HEX": "#231A24"
 },
 {
   "RAL": 5012,
   "RGB": "059-131-189",
   "HEX": "#3B83BD"
 },
 {
   "RAL": 5013,
   "RGB": "037-041-074",
   "HEX": "#1E213D"
 },
 {
   "RAL": 5014,
   "RGB": "096-111-140",
   "HEX": "#606E8C"
 },
 {
   "RAL": 5015,
   "RGB": "034-113-179",
   "HEX": "#2271B3"
 },
 {
   "RAL": 5017,
   "RGB": "006-057-113",
   "HEX": "#063971"
 },
 {
   "RAL": 5018,
   "RGB": "063-136-143",
   "HEX": "#3F888F"
 },
 {
   "RAL": 5019,
   "RGB": "027-085-131",
   "HEX": "#1B5583"
 },
 {
   "RAL": 5020,
   "RGB": "029-051-074",
   "HEX": "#1D334A"
 },
 {
   "RAL": 5021,
   "RGB": "037-109-123",
   "HEX": "#256D7B"
 },
 {
   "RAL": 5022,
   "RGB": "037-040-080",
   "HEX": "#252850"
 },
 {
   "RAL": 5023,
   "RGB": "073-103-141",
   "HEX": "#49678D"
 },
 {
   "RAL": 5024,
   "RGB": "093-155-155",
   "HEX": "#5D9B9B"
 },
 {
   "RAL": 5025,
   "RGB": "042-100-120",
   "HEX": "#2A6478"
 },
 {
   "RAL": 5026,
   "RGB": "016-044-084",
   "HEX": "#102C54"
 },
 {
   "RAL": 6000,
   "RGB": "049-102-080",
   "HEX": "#316650"
 },
 {
   "RAL": 6001,
   "RGB": "040-114-051",
   "HEX": "#287233"
 },
 {
   "RAL": 6002,
   "RGB": "045-087-044",
   "HEX": "#2D572C"
 },
 {
   "RAL": 6003,
   "RGB": "066-070-050",
   "HEX": "#424632"
 },
 {
   "RAL": 6004,
   "RGB": "031-058-061",
   "HEX": "#1F3A3D"
 },
 {
   "RAL": 6005,
   "RGB": "047-069-056",
   "HEX": "#2F4538"
 },
 {
   "RAL": 6006,
   "RGB": "062-059-050",
   "HEX": "#3E3B32"
 },
 {
   "RAL": 6007,
   "RGB": "052-059-041",
   "HEX": "#343B29"
 },
 {
   "RAL": 6008,
   "RGB": "057-053-042",
   "HEX": "#39352A"
 },
 {
   "RAL": 6009,
   "RGB": "049-055-043",
   "HEX": "#31372B"
 },
 {
   "RAL": 6010,
   "RGB": "053-104-045",
   "HEX": "#35682D"
 },
 {
   "RAL": 6011,
   "RGB": "088-114-070",
   "HEX": "#587246"
 },
 {
   "RAL": 6012,
   "RGB": "052-062-064",
   "HEX": "#343E40"
 },
 {
   "RAL": 6013,
   "RGB": "108-113-086",
   "HEX": "#6C7156"
 },
 {
   "RAL": 6014,
   "RGB": "071-064-046",
   "HEX": "#47402E"
 },
 {
   "RAL": 6015,
   "RGB": "059-060-054",
   "HEX": "#3B3C36"
 },
 {
   "RAL": 6016,
   "RGB": "030-089-069",
   "HEX": "#1E5945"
 },
 {
   "RAL": 6017,
   "RGB": "076-145-065",
   "HEX": "#4C9141"
 },
 {
   "RAL": 6018,
   "RGB": "087-166-057",
   "HEX": "#57A639"
 },
 {
   "RAL": 6019,
   "RGB": "189-236-182",
   "HEX": "#BDECB6"
 },
 {
   "RAL": 6020,
   "RGB": "046-058-035",
   "HEX": "#2E3A23"
 },
 {
   "RAL": 6021,
   "RGB": "137-172-118",
   "HEX": "#89AC76"
 },
 {
   "RAL": 6022,
   "RGB": "037-034-027",
   "HEX": "#25221B"
 },
 {
   "RAL": 6024,
   "RGB": "048-132-070",
   "HEX": "#308446"
 },
 {
   "RAL": 6025,
   "RGB": "061-100-045",
   "HEX": "#3D642D"
 },
 {
   "RAL": 6026,
   "RGB": "001-093-082",
   "HEX": "#015D52"
 },
 {
   "RAL": 6027,
   "RGB": "132-195-190",
   "HEX": "#84C3BE"
 },
 {
   "RAL": 6028,
   "RGB": "044-085-069",
   "HEX": "#2C5545"
 },
 {
   "RAL": 6029,
   "RGB": "032-096-061",
   "HEX": "#20603D"
 },
 {
   "RAL": 6032,
   "RGB": "049-127-067",
   "HEX": "#317F43"
 },
 {
   "RAL": 6033,
   "RGB": "073-126-118",
   "HEX": "#497E76"
 },
 {
   "RAL": 6034,
   "RGB": "127-181-181",
   "HEX": "#7FB5B5"
 },
 {
   "RAL": 6035,
   "RGB": "028-084-045",
   "HEX": "#1C542D"
 },
 {
   "RAL": 6036,
   "RGB": "022-053-055",
   "HEX": "#193737"
 },
 {
   "RAL": 6037,
   "RGB": "000-143-057",
   "HEX": "#008F39"
 },
 {
   "RAL": 6038,
   "RGB": "000-187-045",
   "HEX": "#00BB2D"
 },
 {
   "RAL": 7000,
   "RGB": "120-133-139",
   "HEX": "#78858B"
 },
 {
   "RAL": 7001,
   "RGB": "138-149-151",
   "HEX": "#8A9597"
 },
 {
   "RAL": 7002,
   "RGB": "126-123-082",
   "HEX": "#7E7B52"
 },
 {
   "RAL": 7003,
   "RGB": "108-112-089",
   "HEX": "#6C7059"
 },
 {
   "RAL": 7004,
   "RGB": "150-153-146",
   "HEX": "#969992"
 },
 {
   "RAL": 7005,
   "RGB": "100-107-099",
   "HEX": "#646B63"
 },
 {
   "RAL": 7006,
   "RGB": "109-101-082",
   "HEX": "#6D6552"
 },
 {
   "RAL": 7008,
   "RGB": "106-095-049",
   "HEX": "#6A5F31"
 },
 {
   "RAL": 7009,
   "RGB": "077-086-069",
   "HEX": "#4D5645"
 },
 {
   "RAL": 7010,
   "RGB": "076-081-074",
   "HEX": "#4C514A"
 },
 {
   "RAL": 7011,
   "RGB": "067-075-077",
   "HEX": "#434B4D"
 },
 {
   "RAL": 7012,
   "RGB": "078-087-084",
   "HEX": "#4E5754"
 },
 {
   "RAL": 7013,
   "RGB": "070-069-049",
   "HEX": "#464531"
 },
 {
   "RAL": 7015,
   "RGB": "067-071-080",
   "HEX": "#434750"
 },
 {
   "RAL": 7016,
   "RGB": "041-049-051",
   "HEX": "#293133"
 },
 {
   "RAL": 7021,
   "RGB": "035-040-043",
   "HEX": "#23282B"
 },
 {
   "RAL": 7022,
   "RGB": "051-047-044",
   "HEX": "#332F2C"
 },
 {
   "RAL": 7023,
   "RGB": "104-108-094",
   "HEX": "#686C5E"
 },
 {
   "RAL": 7024,
   "RGB": "071-074-081",
   "HEX": "#474A51"
 },
 {
   "RAL": 7026,
   "RGB": "047-053-059",
   "HEX": "#2F353B"
 },
 {
   "RAL": 7030,
   "RGB": "139-140-122",
   "HEX": "#8B8C7A"
 },
 {
   "RAL": 7031,
   "RGB": "071-075-078",
   "HEX": "#474B4E"
 },
 {
   "RAL": 7032,
   "RGB": "184-183-153",
   "HEX": "#B8B799"
 },
 {
   "RAL": 7033,
   "RGB": "125-132-113",
   "HEX": "#7D8471"
 },
 {
   "RAL": 7034,
   "RGB": "143-139-102",
   "HEX": "#8F8B66"
 },
 {
   "RAL": 7035,
   "RGB": "203-208-204",
   "HEX": "#CBD0CC"
 },
 {
   "RAL": 7036,
   "RGB": "127-118-121",
   "HEX": "#7F7679"
 },
 {
   "RAL": 7037,
   "RGB": "125-127-120",
   "HEX": "#7D7F7D"
 },
 {
   "RAL": 7038,
   "RGB": "195-195-195",
   "HEX": "#B5B8B1"
 },
 {
   "RAL": 7039,
   "RGB": "108-105-096",
   "HEX": "#6C6960"
 },
 {
   "RAL": 7040,
   "RGB": "157-161-170",
   "HEX": "#9DA1AA"
 },
 {
   "RAL": 7042,
   "RGB": "141-148-141",
   "HEX": "#8D948D"
 },
 {
   "RAL": 7043,
   "RGB": "078-084-082",
   "HEX": "#4E5452"
 },
 {
   "RAL": 7044,
   "RGB": "202-196-176",
   "HEX": "#CAC4B0"
 },
 {
   "RAL": 7045,
   "RGB": "144-144-144",
   "HEX": "#909090"
 },
 {
   "RAL": 7046,
   "RGB": "130-137-143",
   "HEX": "#82898F"
 },
 {
   "RAL": 7047,
   "RGB": "208-208-208",
   "HEX": "#D0D0D0"
 },
 {
   "RAL": 7048,
   "RGB": "137-129-118",
   "HEX": "#898176"
 },
 {
   "RAL": 8000,
   "RGB": "130-108-052",
   "HEX": "#826C34"
 },
 {
   "RAL": 8001,
   "RGB": "149-095-032",
   "HEX": "#955F20"
 },
 {
   "RAL": 8002,
   "RGB": "108-059-042",
   "HEX": "#6C3B2A"
 },
 {
   "RAL": 8003,
   "RGB": "115-066-034",
   "HEX": "#734222"
 },
 {
   "RAL": 8004,
   "RGB": "142-064-042",
   "HEX": "#8E402A"
 },
 {
   "RAL": 8007,
   "RGB": "089-053-031",
   "HEX": "#59351F"
 },
 {
   "RAL": 8008,
   "RGB": "111-079-040",
   "HEX": "#6F4F28"
 },
 {
   "RAL": 8011,
   "RGB": "091-058-041",
   "HEX": "#5B3A29"
 },
 {
   "RAL": 8012,
   "RGB": "089-035-033",
   "HEX": "#592321"
 },
 {
   "RAL": 8014,
   "RGB": "056-044-030",
   "HEX": "#382C1E"
 },
 {
   "RAL": 8015,
   "RGB": "099-058-052",
   "HEX": "#633A34"
 },
 {
   "RAL": 8016,
   "RGB": "076-047-039",
   "HEX": "#4C2F27"
 },
 {
   "RAL": 8017,
   "RGB": "069-050-046",
   "HEX": "#45322E"
 },
 {
   "RAL": 8019,
   "RGB": "064-058-058",
   "HEX": "#403A3A"
 },
 {
   "RAL": 8022,
   "RGB": "033-033-033",
   "HEX": "#212121"
 },
 {
   "RAL": 8023,
   "RGB": "166-094-046",
   "HEX": "#A65E2E"
 },
 {
   "RAL": 8024,
   "RGB": "121-085-061",
   "HEX": "#79553D"
 },
 {
   "RAL": 8025,
   "RGB": "117-092-072",
   "HEX": "#755C48"
 },
 {
   "RAL": 8028,
   "RGB": "078-059-049",
   "HEX": "#4E3B31"
 },
 {
   "RAL": 8029,
   "RGB": "118-060-040",
   "HEX": "#763C28"
 },
 {
   "RAL": 9001,
   "RGB": "250-244-227",
   "HEX": "#FDF4E3"
 },
 {
   "RAL": 9002,
   "RGB": "231-235-218",
   "HEX": "#E7EBDA"
 },
 {
   "RAL": 9003,
   "RGB": "244-244-244",
   "HEX": "#F4F4F4"
 },
 {
   "RAL": 9004,
   "RGB": "040-040-040",
   "HEX": "#282828"
 },
 {
   "RAL": 9005,
   "RGB": "010-010-013",
   "HEX": "#0A0A0A"
 },
 {
   "RAL": 9006,
   "RGB": "165-165-165",
   "HEX": "#A5A5A5"
 },
 {
   "RAL": 9007,
   "RGB": "143-143-143",
   "HEX": "#8F8F8F"
 },
 {
   "RAL": 9010,
   "RGB": "255-255-255",
   "HEX": "#FFFFFF"
 },
 {
   "RAL": 9011,
   "RGB": "028-028-028",
   "HEX": "#1C1C1C"
 },
 {
   "RAL": 9016,
   "RGB": "246-246-246",
   "HEX": "#F6F6F6"
 },
 {
   "RAL": 9017,
   "RGB": "030-030-030",
   "HEX": "#1E1E1E"
 },
 {
   "RAL": 9018,
   "RGB": "207-211-205",
   "HEX": "#CFD3CD"
 },
 {
   "RAL": 9022,
   "RGB": "156-156-156",
   "HEX": "#9C9C9C"
 },
 {
   "RAL": 9023,
   "RGB": "130-130-130",
   "HEX": "#828282"
 }
]';
    $colors = json_decode($colors, true);
    
    $counter = 0;
    $attributes = Attribute::get();
    foreach($attributes as $attribute){
      $values = json_decode($attribute->values, true);
      if($attribute->type == 0){
        foreach($values as $val){
          $createdAt = date("Y-m-d H:i:s");
          $dimension = new Dimension();
          $dimension->value = $val;
          $dimension->created_at = $createdAt;
          $dimension->updated_at = $createdAt;
          $dimension->save();
          
          $attrVal = new AttributeDimension();
          $attrVal->attribute_id = $attribute->id;
          $attrVal->dimension_id = $dimension->id;
          $attrVal->created_at = $createdAt;
          $attrVal->updated_at = $createdAt;
          $attrVal->save();
          $counter++;
        }
      } else{
        foreach($values as $val){
          $createdAt = date("Y-m-d H:i:s");
          // daca am culoare, iau id-ul de culoare
          $col = strtoupper(array_values($val)[0]);
          $color = Color::where('ral', $col)->first();
          if($color == null){
            // daca nu am culoarea, o salvez
            $color = new Color;
            $color->value = "#000000";
            $color->ral = $col;
            $color->created_at = $createdAt;
            $color->updated_at = $createdAt;
            $color->save();
          }
          $attrVal = new AttributeColor();
          $attrVal->attribute_id = $attribute->id;
          $attrVal->color_id = $color->id;
          $attrVal->created_at = $createdAt;
          $attrVal->updated_at = $createdAt;
          $attrVal->save();
          $counter++;
        }
      }
    }
    dd('Inserted '.$counter.' elements.');
    // inserare culori
    $counter = 0;
    foreach($colors as $color){
      $createdAt = date("Y-m-d H:i:s");
      $col = new Color;
      $col->value = $color['HEX'];
      $col->ral = $color['RAL'];
      $col->created_at = $createdAt;
      $col->updated_at = $createdAt;
      $col->save();
      $counter++;
    }
    dd('Inserted '.$counter.' elements.');
//     dd($colors);
    $attrs = json_decode($attrs, true);
    $newAttrs = [];
    foreach($attrs as $col){
      $newAttrs[$col['name']] = [];
    }
    foreach($attrs as $col){
      if(strpos(strtolower($col['name']), 'culoare') !== false){
        $hexColor = $this->getColorHex($col['value RAL'], $colors);
        array_push($newAttrs[$col['name']], [
          $hexColor => $col['value RAL']
        ]);
        dump('Pushing '.$hexColor.'=>'.$col['value RAL'].' to '.$col['name']);
      } else{
        dump('Pushing '.$col['value RAL'].' to '.$col['name']);
        array_push($newAttrs[$col['name']], $col['value RAL']);
      }
    }
    $counter = 0;
    foreach($newAttrs as $key => $attr){
      $dbAttr = Attribute::where('title', $key)->first();
      $dbAttr->values = json_encode($attr);
      $dbAttr->save();
      $counter++;
    }
    dd('Updated '.$counter.' elements.');
  }
  
  public function getColorHex($ral, $colors){
    $foundedColor = '#000000';
    foreach($colors as $color){
      if(strpos($ral, $color['RAL']."") !== false){
        $foundedColor = $color['HEX'];
        break;
      }
    }
    return $foundedColor;
  }
  
}
