#!/usr/bin/perl

foreach $line (<STDIN>){
    chomp;
    if ($line =~ /192\.168|127\.0/) {
        next;
    }
    if ($line =~ /Failed password for illegal user/) {
        @line = split (/ /, $line);
        $ip = $line[12];
        if ($ip == "from" ) {
            $ip = $line[13];
        }
        
        $email = do_lookup ($ip);
        do_notify($email, $ip, $line);
    }
}

sub do_lookup {
    $ip = pack("A*", @_);
    print "ip is $ip\n";

    `whois -a @_ > /tmp/whois.@_.$$`;
    $file = "/tmp/whois.@_.$$";

    open(FILE, $file) or die "Error: $!\n";

    foreach $line (<FILE>) {
        chomp;
        if ($line =~ /ReferralServer/) {
            @referral_server = split (/\/\//, $line);
            print "Referral Server found: $referral_server[1] Forking whois..\n";
            print "IP: $ip\n";
            open(WHOIS, "whois $ip -h $referral_server[1] |") or die "Error: $!\n";
            foreach $line (<WHOIS>) {
                chomp;
                if ($line =~ /e-mail:/) {
                    @org_tech_email = split (/:/, $line);
                    $email = $org_tech_email[1];
                    print "OrgTechEmail: $org_tech_email[1]\n";
                    do_notify($org_tech_email[1], $ip);
                    close(WHOIS);
                    return $email;
                }
            }        
        } 
        if ($line =~ /OrgTechEmail|TechEmail|OrgAbuseEmail/) {
                @org_tech_email = split (/ /, $line);
                $email = $org_tech_email[2];
                print "OrgTechEmail: $org_tech_email[2]\n";
                return $email;
                do_notify($org_tech_email[2], $ip);
        }
    }
}

sub do_notify {
    $email = $_[0];
    $ip = $_[1];
    $msg = $_[2];

    print "$msg";
    `echo "$msg" | mail -c root -s "SSH Breakin Attempt detected from your $ip, check your system!" $email\n`; 
}   
    

