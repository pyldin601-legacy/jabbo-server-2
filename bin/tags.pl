use Image::Size;
use Encode;
require "$Bin/Mediainfo.pm";

sub get_tags() {
	my $input = detransliterate(shift);
	my @wd = split(/[\s|\$|\^|\&|\/|\\|\[|\]|\||\*|\_|\-|\(|\)|\.|\,|\'|\<|\>|\"|\+|\#]+/, $input);
	my %tmp = ();
	@wd = grep {! $tmp{$_}++ } @wd;
	my @ok = ();
	for my $i (3..$#wd) {
		push(@ok, $wd[$i]) if (length($wd[$i]) > 2);
	}
	return join(',', @ok);
}

sub detransliterate() {
    my $input = encode('utf-8', lc(pack('U0C*', unpack('C*', shift))));
    my %replace =  ('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
                    'е' => 'e', 'ё' => 'yo', 'ж' => 'j', 'з' => 'z', 'и' => 'i',
                    'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
                    'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
                    'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
                    'ш' => 'sh', 'щ' => 'sch', 'ь' =>  '', 'ы' => 'y', 'ъ' => '',
                    'э' => 'e', 'ю' => 'yu', 'я' => 'ya');

    foreach $key (keys %replace) {
	$input =~ s/$key/$replace{$key}/g;
    }

    return $input;
}

sub get_file_info() {
	my $filename = shift;
	my $fclass = shift;

	my $video_dimension;
	my $avg_bitrate;
	my $avg_duration;
	my $audio_artist;
	my $audio_band;
	my $audio_title;
	my $audio_album;
	my $audio_tracknum;
	my $audio_year;
	my $audio_cover;
	my $audio_genre;

	if($fclass eq 'audio') {
		my $media = new Mediainfo("filename" => $filename);
		$avg_bitrate = $media->{bitrate};
		$avg_duration = $media->{length};
		$audio_artist = $media->{trackartist};
		$audio_band = $media->{trackband};
		$audio_album = $media->{trackalbum};
		$audio_title = $media->{tracktitle};
		$audio_tracknum = $media->{tracknumber};
		$audio_year = $media->{trackyear};
		$audio_genre = $media->{trackgenre};
		$audio_cover = $media->{trackcover};
	}
	if($fclass eq 'video') {
		my $media = new Mediainfo("filename" => $filename);
		$avg_bitrate = $media->{bitrate};
		$avg_duration = $media->{length};
		$video_dimension = $media->{width} . 'x' . $media->{height};
	}
	if($fclass eq 'image') {
		my ($w, $h) = imgsize($filename);
		$video_dimension = $w . 'x' . $h;
	}
	return ($video_dimension, 
			int($avg_bitrate), 
			int($avg_duration), 
			$audio_artist, 
			$audio_band, 
			$audio_title, 
			$audio_album, 
			$audio_tracknum,
			$audio_genre
	);
}


return 1;