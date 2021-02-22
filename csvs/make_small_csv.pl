#!/usr/bin/perl

# Use necessary packages
use Cwd;

# Parse command line opts
use Getopt::Std;
use vars qw($opt_h $opt_m $opt_s $opt_f);
getopts('hms:f:');

&help if ($opt_h);

# Get the input filename/directory defaults
# print "INPUT_FILE should be $opt_f\n";
my $INPUT_FILE = ($opt_f && -f $opt_f) ? $opt_f : ""; # Defaults to empty string/no file
# print "It really is $INPUT_FILE\n";
if ( ! -e $opt_f ) {
  # print "The file does not exist\n";
}
# Make sure a filename given on the command line actually exists
&help("Bad filename or directory: $opt_f") if ($opt_f && ! -e $opt_f);
# Needs an input file
&help("Filename required (-f <filename>)") if ($INPUT_FILE eq "");

# Get the number of lines to skip (will keep 1/<number_to_skip> lines)
my $NUMBER_TO_SKIP = ($opt_s) ? $opt_s : 100; # default 100 (keep 1%)
# Make sure the number to skip is actually an integer
&help("Number of lines to skip must be an integer") if ($NUMBER_TO_SKIP !~ /^[0-9]+$/);

# Output file name (<INPUT_FILE>-small.csv)
my $OUTPUT_FILE = $INPUT_FILE;
$OUTPUT_FILE =~ s/^(.*)\.csv$/$1-small.csv/;

# Open the files
open(INPUT_FH, '<', $INPUT_FILE) or die "Can't open $INPUT_FILE for reading: $!";
if ( ! $opt_m ) {
  open(OUTPUT_FH, '>', $OUTPUT_FILE) or die "Can't open $OUTPUT_FILE for writing: $!";
}

# Assume the first line is the heading, print it to the output
my $header = <INPUT_FH>;
print OUTPUT_FH $header if ( ! $opt_m );
# print "wubba wubba\n";
# Iterate across the remaining input lines
my $total_lines = 0;
my $kept_lines = 0;
while (<INPUT_FH>) {
  # print "Looking at a line\n";
  # Decide if we're going to print it
  if ( rand($NUMBER_TO_SKIP) < 1 ) {
    print OUTPUT_FH $_ if ( ! $opt_m );
    $kept_lines += 1;
    # print "kept_lines is now $kept_lines\n";
  }
  $total_lines += 1;
}

# Print the stats
printf( "Input file had %d lines, %d were kept\n", $total_lines, $kept_lines );

# Close the file handles
close INPUT_FH;
close OUTPUT_FH;

sub help {
  my $err_msg = scalar(@_) ? "\nERROR: " . pop(@_) . "\n--------------\n" : "";
  print $err_msg . "Make Small CSV\n" .
        "  Usage: make_small_csv.pl [-f <input_csv>] [-s <number_to_skip>] [-h]\n" .
        "  Options: -f <file_dir>       : Filename or directory of the input file(s)\n" .
        "           -s <number_to_skip> : Number of lines to skip (on average)\n" .
        "                                 default: 100 (keep 1% of original lines)\n" .
        "           -m                  : Mock run, only gives stats\n" .
        "           -h                  : Print this help message\n\n";
  exit();
}
